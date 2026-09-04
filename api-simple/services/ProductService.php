<?php

/**
 * SERVICE DE PRODUCTOS
 * ==================================================================
 * ACA VIVEN LAS REGLAS DEL NEGOCIO.
 *
 * "Regla del negocio" es una decision del sistema, no de la
 * programacion. Por ejemplo:
 *
 *   - no puede haber dos productos con el mismo nombre
 *   - no se puede vender mas stock del que hay
 *   - no se puede borrar un producto que todavia tiene mercaderia
 *
 * El service es el que decide si algo se puede hacer o no.
 * El controller solo recibe el pedido, y el repository solo guarda.
 * ==================================================================
 */
class ProductService
{
    private ProductRepository $productRepository;

    public function __construct()
    {
        $this->productRepository = new ProductRepository();
    }

    /** Devuelve la lista de productos, lista para mandar como JSON. */
    public function getAll($category = null)
    {
        $products = $this->productRepository->findAll($category);

        $list = [];

        foreach ($products as $product) {
            $list[] = $product->toArray();
        }

        return $list;
    }

    /** Devuelve un producto. Si no existe, corta con un 404. */
    public function getById($id)
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            // Response::error() contesta y TERMINA el programa,
            // asi que lo que sigue no se ejecuta.
            Response::error('No existe el producto ' . $id, 404);
        }

        return $product->toArray();
    }

    /**
     * Crea un producto.
     *
     * REGLA: no puede haber dos productos con el mismo nombre.
     */
    public function create($name, $description, $price, $stock, $category)
    {
        if ($this->productRepository->findByName($name) !== null) {
            Response::error('Ya existe un producto con ese nombre.', 400);
        }

        $product = new Product(0, $name, $description, $price, $stock, $category);

        $this->productRepository->create($product);

        return $product->toArray();
    }

    /**
     * Modifica los campos que hayan mandado.
     */
    public function update($id, $data)
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            Response::error('No existe el producto ' . $id, 404);
        }

        // La regla del nombre unico tambien se aplica cuando se modifica.
        // Si encontramos otro producto con ese nombre, el cambio se rechaza.
        if (isset($data['nombre'])) {
            $productWithSameName = $this->productRepository->findByName($data['nombre']);

            if ($productWithSameName !== null
                && $productWithSameName->getId() !== $product->getId()) {
                Response::error('Ya existe un producto con ese nombre.', 400);
            }
        }

        // Solo tocamos lo que vino. Los setters se encargan de que el
        // objeto no quede en un estado imposible. La forma de los datos
        // ya fue validada en el controller.
        if (isset($data['nombre'])) {
            $product->setName($data['nombre']);
        }

        if (isset($data['descripcion'])) {
            $product->setDescription($data['descripcion']);
        }

        if (isset($data['precio'])) {
            $product->setPrice($data['precio']);
        }

        if (isset($data['stock'])) {
            $product->setStock($data['stock']);
        }

        if (isset($data['categoria'])) {
            $product->setCategory($data['categoria']);
        }

        $this->productRepository->update($product);

        return $product->toArray();
    }

    /**
     * Elimina un producto.
     *
     * REGLA: no se puede borrar si todavia queda mercaderia en stock.
     * Primero hay que venderla o darla de baja.
     */
    public function delete($id)
    {
        $product = $this->productRepository->findById($id);

        if ($product === null) {
            Response::error('No existe el producto ' . $id, 404);
        }

        if ($product->hasStock()) {
            Response::error(
                'No se puede borrar: todavia quedan ' . $product->getStock() . ' unidades en stock.',
                400
            );
        }

        $this->productRepository->delete($id);
    }

    /**
     * VENDER: el ejemplo mas claro de para que existe esta capa.
     * ------------------------------------------------------------------
     * Vender no es "guardar un dato": es una OPERACION con reglas y con
     * varios pasos que tienen que pasar juntos.
     *
     * Este metodo no podria vivir en el controller, porque manana la
     * venta la va a hacer tambien el sistema de la caja, o una app, o
     * un proceso que importa un archivo de pedidos. Todos van a llamar
     * a este mismo metodo.
     */
    public function sell($id, $quantity)
    {
        $product = $this->productRepository->findById($id);

        // Regla 1: el producto tiene que existir.
        if ($product === null) {
            Response::error('No existe el producto ' . $id, 404);
        }

        // Regla 2: hay que vender al menos una unidad.
        if (!is_numeric($quantity) || $quantity < 1) {
            Response::error('La cantidad tiene que ser 1 o mas.', 400);
        }

        // Regla 3: no se puede vender mas de lo que hay.
        if ($product->getStock() < $quantity) {
            Response::error(
                'No hay stock suficiente. Quedan ' . $product->getStock() . ' unidades.',
                400
            );
        }

        // Si paso todas las reglas: descontamos y guardamos.
        $product->setStock($product->getStock() - $quantity);

        $this->productRepository->update($product);

        return [
            'vendidas'      => (int) $quantity,
            'total_a_pagar' => $quantity * $product->getPrice(),
            'producto'      => $product->toArray(),
        ];
    }
}
