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

    public function listReviewsByProduct($productId)
    {
        $sql = 'SELECT * FROM reviews WHERE producto_id = :productId';

        $query = $this->db->prepare($sql);
        $query->execute([':productId' => $productId]);
        $rows = $query->fetchAll();

        $data = array_map([$this, 'buildReview'], $rows);

        if (empty($data)) {
            return [];
        }
        return $data;
    }

    public function createReview($userId, $productId, $score, $comment)
    {
        $sql = 'INSERT INTO reviews (usuario_id, producto_id, puntuacion, comentario) VALUES (:userId, :productId, :score, :comment)';

        $query = $this->db->prepare($sql);
        $query->execute([
            ':userId' => $userId,
            ':productId' => $productId,
            ':score' => $score,
            ':comment' => $comment
        ]);

        // Obtener el ID de la review recién creada
        $reviewId = $this->db->lastInsertId();

        // Devolver la review recién creada como objeto Review
        return new Review($reviewId, $userId, $productId, $score, $comment, date('Y-m-d H:i:s'));
    }

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
