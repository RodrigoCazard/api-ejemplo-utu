<?php

/**
 * CONTROLLER DE REVIEWS
 * ==================================================================
 * ESQUELETO. Faltan dos metodos, cada uno para un case del switch de
 * index.php (ya estan los case comentados, esperando el nombre):
 *
 *   listReviewsByProduct($productId) -> GET  /productos/{id}/reviews
 *   createReview($productId)         -> POST /productos/{id}/reviews  (login)
 *
 * Miren controllers/VentaController.php y controllers/ProductController.php
 * como espejo: leer el pedido, validar la forma de los datos (que
 * 'puntuacion' venga y sea un entero entre 1 y 5), llamar al service,
 * responder con Response::success().
 *
 * requireLogin() (en core/helpers.php) devuelve el arreglo del
 * usuario logueado (con 'id' y 'rol'): lo van a necesitar para el
 * usuario_id al crear una review.
 * ==================================================================
 */
class ReviewController
{
    private ReviewService $reviewService;

    public function __construct()
    {
        $this->reviewService = new ReviewService();
    }

    // ------------------------------------------------------------------
    // Agregar aca listReviewsByProduct() y createReview()
    // ------------------------------------------------------------------

    /**
     * Valida los IDs de la URL sin crear todavia una clase Validator.
     */
    private function validateId($id): int
    {
        if (filter_var($id, FILTER_VALIDATE_INT) === false || $id < 1) {
            Response::error('El ID no es valido.', 400);
        }

        return (int) $id;
    }
}
