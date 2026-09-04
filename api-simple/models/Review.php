<?php

/**
 * CLASE REVIEW
 * ==================================================================
 * Igual que Venta: una vez creada, una review no se edita ni se
 * anula. Es un objeto INMUTABLE (solo getters, sin setters).
 * ==================================================================
 */
class Review
{
    private int $id;
    private int $userId;
    private int $productId;
    private int $score;
    private ?string $comment;
    private string $date;

    public function __construct($id, $userId, $productId, $score, $comment, $date)
    {
        $this->id        = $id;
        $this->userId    = $userId;
        $this->productId = $productId;
        $this->score     = $score;
        $this->comment   = $comment;
        $this->date      = $date;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function toArray()
    {
        return [
            'id'          => $this->id,
            'usuario_id'  => $this->userId,
            'producto_id' => $this->productId,
            'puntuacion'  => $this->score,
            'comentario'  => $this->comment,
            'fecha'       => $this->date,
        ];
    }
}
