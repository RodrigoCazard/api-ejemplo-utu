<?php

/**
 * REPOSITORIO DE VENTAS  (clase HIJA de Repository)
 * ==================================================================
 * ESQUELETO DEL EJERCICIO GRUPAL. Ver docs/ejercicio-nueva-entidad.md.
 *
 * Cada grupo agrega ADENTRO de esta clase, al final, el metodo que
 * necesita para SU endpoint. No borren ni modifiquen el metodo de
 * otro grupo: si dos grupos necesitan tocar el mismo archivo al mismo
 * tiempo, coordinense.
 *
 * Ya viene resuelto buildVenta(): lo usan todos los metodos nuevos
 * para convertir una fila de la base en un objeto Venta, igual que
 * hace ProductRepository::buildProduct(). Miren
 * repositories/ProductRepository.php como espejo de como se escribe
 * cada metodo (findAll, findById, create, update...).
 * ==================================================================
 */
class VentaRepository extends Repository
{
    // ------------------------------------------------------------------
    // Agreguen aca su metodo. Ejemplos segun el endpoint que les toque
    
    //
    //   findAll($userId = null)         -> GET /ventas
    //   findById($id)                   -> GET /ventas/{id}
    //   create(Venta $venta)            -> POST /ventas
    //   update(Venta $venta)            -> POST /ventas/{id}/anular
    //   findByProduct($productId)       -> GET /productos/{id}/ventas
    //   getTotals()                     -> GET /ventas/resumen (COUNT/SUM)
    // ------------------------------------------------------------------

    /** De fila de la base (arreglo) a OBJETO Venta. Ya esta resuelto. */
    private function buildVenta($row)
    {
        return new Venta(
            $row['id'],
            $row['usuario_id'],
            $row['producto_id'],
            $row['cantidad'],
            $row['precio_unitario'],
            $row['total'],
            $row['estado'],
            $row['fecha']
        );
    }
}
