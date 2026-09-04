<?php

/**
 * REPOSITORIO DE REVIEWS  (clase HIJA de Repository)
 * ==================================================================
 * ESQUELETO. Entidad nueva minima, mismo espiritu que el ejercicio
 * grupal de Ventas (ver docs/ejercicio-nueva-entidad.md).
 *
 * Faltan dos metodos:
 *
 *   listReviewsByProduct($productId) -> GET  /productos/{id}/reviews
 *   create(Review $review)           -> POST /productos/{id}/reviews
 *
 * Ya viene resuelto buildReview(): lo usan los metodos nuevos para
 * convertir una fila de la base en un objeto Review, igual que hace
 * ProductRepository::buildProduct(). Miren repositories/VentaRepository.php
 * y repositories/ProductRepository.php como espejo.
 * ==================================================================
 */
class ReviewRepository extends Repository
{
    // ------------------------------------------------------------------
    // Agregar aca listReviewsByProduct() y create()
    // ------------------------------------------------------------------


    /** De fila de la base (arreglo) a OBJETO Review. Ya esta resuelto. */
    private function buildReview($row)
    {
        return new Review(
            $row['id'],
            $row['usuario_id'],
            $row['producto_id'],
            $row['puntuacion'],
            $row['comentario'],
            $row['fecha']
        );
    }
}
