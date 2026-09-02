<?php

/**
 * CLASE VENTA
 * ==================================================================
 * A diferencia de Product, esta clase casi no tiene setters.
 *
 * Por que: una venta ya hecha no se "edita". Si algo salio mal, no se
 * corrige el monto ni la cantidad: se ANULA (y eso devuelve el stock
 * del producto). Por eso el UNICO cambio permitido despues de creada
 * es cancel(), que solo puede mover el estado de "confirmada" a
 * "anulada". El resto de los datos nace y se queda igual para
 * siempre: es la idea de un objeto INMUTABLE, con una sola excepcion
 * controlada.
 * ==================================================================
 */
class Venta
{
    private int $id;
    private int $userId;
    private int $productId;
    private int $quantity;
    private float $unitPrice;
    private float $total;
    private string $status;
    private string $date;

    public function __construct($id, $userId, $productId, $quantity, $unitPrice, $total, $status, $date)
    {
        $this->id        = $id;
        $this->userId    = $userId;
        $this->productId = $productId;
        $this->quantity  = $quantity;
        $this->unitPrice = $unitPrice;
        $this->total     = $total;
        $this->status    = $status;
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

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isCancelled()
    {
        return $this->status === 'anulada';
    }

    public function getDate()
    {
        return $this->date;
    }

    /**
     * La UNICA modificacion permitida: anular la venta.
     * La usa el grupo que haga POST /ventas/{id}/anular.
     */
    public function cancel()
    {
        $this->status = 'anulada';
    }

    public function toArray()
    {
        return [
            'id'              => $this->id,
            'usuario_id'      => $this->userId,
            'producto_id'     => $this->productId,
            'cantidad'        => $this->quantity,
            'precio_unitario' => $this->unitPrice,
            'total'           => $this->total,
            'estado'          => $this->status,
            'fecha'           => $this->date,
        ];
    }
}
