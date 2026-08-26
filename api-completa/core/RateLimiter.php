<?php

// FilesystemAdapter es una forma de cache de Symfony.
// En vez de guardar el conteo nosotros en un JSON, Symfony guarda su propio
// estado dentro de storage/cache.
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// RateLimiterFactory crea limitadores. No limita por si sola: fabrica un
// limiter configurado con una regla, por ejemplo "60 pedidos por minuto".
use Symfony\Component\RateLimiter\RateLimiterFactory;

// CacheStorage conecta el rate limiter con un lugar donde guardar estado.
// El rate limiter necesita recordar requests anteriores para saber si la IP
// ya paso el limite.
use Symfony\Component\RateLimiter\Storage\CacheStorage;

/**
 * RATE LIMITER CON LIBRERIA
 * ------------------------------------------------------------------
 * Objetivo: evitar que una misma IP haga demasiadas peticiones seguidas.
 *
 * Si una IP supera el limite, la API responde:
 *
 *   429 Too Many Requests
 *
 * Este archivo NO implementa el algoritmo a mano. Para eso usamos la libreria
 * symfony/rate-limiter, instalada con Composer.
 *
 * Nuestra clase solo hace de "adaptador" entre Symfony y nuestra API:
 * - configura el limite;
 * - identifica al cliente por IP;
 * - consume 1 permiso por request;
 * - responde JSON si ya no quedan permisos.
 */
class RateLimiter
{
    /**
     * Revisa si la request actual puede pasar.
     *
     * Ejemplo:
     *
     *   RateLimiter::check(60, 60);
     *
     * Significa:
     *
     *   maximo 60 peticiones cada 60 segundos por IP.
     */
    public static function check($maxRequests = 60, $seconds = 60)
    {
        /**
         * 1) STORAGE: donde Symfony guarda el estado del limite.
         *
         * HTTP no recuerda nada entre una request y otra. Entonces el limiter
         * necesita guardar algo como: "esta IP ya hizo X pedidos".
         *
         * Usamos FilesystemAdapter porque es simple para aprender y funciona
         * sin Redis ni base de datos extra.
         *
         * Importante: no estamos leyendo ni escribiendo archivos a mano.
         * Symfony Cache se encarga de eso por nosotros.
         */
        $storage = new CacheStorage(
            new FilesystemAdapter(
                'rate_limiter',              // nombre interno del grupo de cache
                0,                           // tiempo por defecto; 0 deja que Symfony decida
                __DIR__ . '/../storage/cache' // carpeta donde Symfony guarda su cache
            )
        );

        /**
         * 2) FACTORY: configuracion del limite.
         *
         * id:
         *   Nombre del limiter. Sirve para que este limite no se mezcle con
         *   otros limiters que podriamos crear mas adelante.
         *
         * policy: sliding_window
         *   "Ventana deslizante". Mira los pedidos recientes de forma mas
         *   pareja que una ventana fija. Para una API es una buena opcion base.
         *
         * limit:
         *   Cantidad maxima de pedidos permitidos.
         *
         * interval:
         *   Periodo de tiempo donde se cuenta ese limite.
         */
        $factory = new RateLimiterFactory([
            'id' => 'api_completa',
            'policy' => 'sliding_window',
            'limit' => $maxRequests,
            'interval' => $seconds . ' seconds',
        ], $storage);

        /**
         * 3) KEY: a quien estamos limitando.
         *
         * REMOTE_ADDR es la IP que hizo la peticion.
         * Con factory->create($clientIp), Symfony crea un contador separado
         * para cada IP.
         */
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $limiter = $factory->create($clientIp);

        /**
         * 4) CONSUMIR 1 PERMISO.
         *
         * Cada request consume 1 "token" o permiso.
         * Si todavia quedan permisos, isAccepted() devuelve true.
         * Si ya se acabo el limite, devuelve false.
         */
        $limit = $limiter->consume(1);

        /**
         * 5) HEADERS INFORMATIVOS.
         *
         * No son obligatorios, pero ayudan a depurar y son comunes en APIs.
         */
        header('X-RateLimit-Limit: ' . $limit->getLimit());
        header('X-RateLimit-Remaining: ' . $limit->getRemainingTokens());

        // Si Symfony acepta la request, no hacemos nada mas: la API sigue.
        if ($limit->isAccepted()) {
            return;
        }

        /**
         * 6) SI NO QUEDAN PERMISOS.
         *
         * Symfony nos dice cuando conviene intentar de nuevo.
         * Lo convertimos a segundos para mandarlo en Retry-After y en el JSON.
         */
        $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

        // Header estandar: cuantos segundos deberia esperar el cliente.
        header('Retry-After: ' . $retryAfter);

        // Nuestra respuesta JSON normal, pero con status HTTP 429.
        Response::error(
            'Demasiadas peticiones. Espera unos segundos antes de intentar de nuevo.',
            429,
            ['retry_after' => $retryAfter]
        );
    }
}