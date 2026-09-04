<?php

/**
 * CONTROLLER DE VENTAS
 * ==================================================================
 * ESQUELETO DEL EJERCICIO GRUPAL. Ver docs/ejercicio-nueva-entidad.md.
 *
 * Cada grupo agrega ADENTRO de esta clase, al final, el metodo de SU
 * endpoint. No borren ni modifiquen el metodo de otro grupo.
 *
 * Miren controllers/ProductController.php como espejo: mismo trabajo
 * (leer el pedido, validar la forma de los datos, llamar al service,
 * responder) para cada uno de los cuatro endpoints de este ejercicio.
 *
 * requireLogin() les devuelve el arreglo del usuario logueado (con
 * 'id' y 'rol'); lo van a necesitar para decidir que puede ver o
 * anular cada usuario.
 * ==================================================================
 */
class VentaController
{
    private VentaService $ventaService;

    public function __construct()
    {
        $this->ventaService = new VentaService();
    }

    // ------------------------------------------------------------------
    // Agreguen aca su metodo. Cada uno corresponde a UN case nuevo del
    // switch de index.php (ya estan los case comentados, esperando el
    // nombre del metodo). Los 6 endpoints del ejercicio:
    //
    //   listSales()                  -> GET  /ventas
    //   getSale($id)                 -> GET  /ventas/{id}
    //   createSale()                 -> POST /ventas
    //   cancelSale($id)              -> POST /ventas/{id}/anular
    //   listSalesByProduct($id)      -> GET  /productos/{id}/ventas
    //   salesSummary()               -> GET  /ventas/resumen
    // ------------------------------------------------------------------

    public function getSale($id)
        {
            $user = requireLogin();

            $venta = $this->ventaService->getSale($id, $user['id'], $user['rol'] === 'admin');

            Response::success($venta->toArray());

        }
    }
