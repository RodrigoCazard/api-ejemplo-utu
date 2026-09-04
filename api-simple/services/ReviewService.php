<?php

/**
 * SERVICE DE REVIEWS
 * ==================================================================
 * ESQUELETO. Faltan dos metodos:
 *
 *   listReviewsByProduct($productId) -> GET /productos/{id}/reviews
 *       (regla: el producto tiene que existir, si no Response::error(..., 404))
 *
 *   create($userId, $productId, $score, $comment) -> POST /productos/{id}/reviews
 *       (regla: el producto tiene que existir; $score tiene que estar
 *       entre 1 y 5 -esto ya lo puede validar el Controller, aca solo
 *       la regla de "el producto existe")
 *
 * Ya tienen disponibles $this->reviewRepository (ReviewRepository) y
 * $this->productRepository (ProductRepository, para chequear que el
 * producto exista). Miren services/VentaService.php como espejo.
 * ==================================================================
 */
class ReviewService
{
    private ReviewRepository $reviewRepository;
    private ProductRepository $productRepository;

    public function __construct()
    {
        $this->reviewRepository = new ReviewRepository();
        $this->productRepository = new ProductRepository();
    }

    // ------------------------------------------------------------------
    // Agregar aca listReviewsByProduct() y create()
    // ------------------------------------------------------------------

}
