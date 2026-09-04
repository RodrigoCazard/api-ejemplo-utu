<?php

/**
 * SERVICE DE VENTAS
 * ==================================================================
 * ESQUELETO DEL EJERCICIO GRUPAL. Ver docs/ejercicio-nueva-entidad.md.
 *
 * Cada grupo agrega ADENTRO de esta clase, al final, el metodo con la
 * regla de negocio de SU endpoint. No borren ni modifiquen el metodo
 * de otro grupo.
 *
 * Ya tienen disponibles $this->ventaRepository (VentaRepository) y
 * $this->productRepository (ProductRepository, para leer o
 * actualizar el stock del producto vendido). Miren
 * services/ProductService.php como espejo: fijense sobre todo en
 * sell() y en delete(), que son los que mas se parecen a lo que
 * necesitan crear() y anular() de ventas.
 * ==================================================================
 */
class VentaService
{
    private VentaRepository $ventaRepository;
    private ProductRepository $productRepository;

    public function __construct()
    {
        $this->ventaRepository = new VentaRepository();
        $this->productRepository = new ProductRepository();
    }

    // ------------------------------------------------------------------
    // Agreguen aca su metodo. Ejemplos segun el endpoint que les toque
    //
    //   getAll($userId, $isAdmin) -> GET /ventas (regla: si $isAdmin es
    //       true le pasa null al repository -osea, todas-; si no, le
    //       pasa $userId -osea, solo las suyas-)
    //   getById($id, $userId, $isAdmin) -> GET /ventas/{id} (regla:
    //       solo el dueno de la venta o un admin puede verla, si no
    //       Response::error(..., 403))
    //   create($userId, $productId, $quantity) -> POST /ventas (regla:
    //       el producto tiene que existir y tener stock suficiente,
    //       igual que ProductService::sell())
    //   cancel($id, $userId, $isAdmin) -> POST /ventas/{id}/anular
    //       (regla: no se puede anular una venta ya anulada; solo el
    //       dueno o un admin; al anular, hay que devolver el stock al
    //       producto)
    //   getByProduct($productId) -> GET /productos/{id}/ventas (regla:
    //       el producto tiene que existir)
    //   getSummary() -> GET /ventas/resumen (solo junta y devuelve lo
    //       que ya calculo VentaRepository::getTotals())
    // ------------------------------------------------------------------


    public function getSale($id, $userId, $isAdmin)
    {
        $venta = $this->ventaRepository->findById($id);

        if ($venta === null) {
            Response::error('Venta no encontrada.', 404);
        }

        // Solo el dueño de la venta o un admin pueden verla.
        if (!$isAdmin && $venta->getUserId() !== $userId) {
            Response::error('No tenes permiso para ver esta venta.', 403);
        }

        return $venta;
    }
}
