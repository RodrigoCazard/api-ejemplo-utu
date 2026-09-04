<?php

/**
 * CONTROLLER DE PRODUCTOS
 * ==================================================================
 * MIRA LO CORTO QUE ES CADA METODO.
 *
 * Es porque el controller hace solo su trabajo:
 *
 *   1. ?esta logueado?          -> requireLogin()
 *   2. ?los datos vienen bien?  -> validacion
 *   3. le pasa la pelota al service
 *   4. contesta                 -> Response::success()
 *
 * Las REGLAS (que el nombre no se repita, que no se venda mas de lo
 * que hay) no estan aca: estan en ProductService.
 *
 * ------------------------------------------------------------------
 * LOS NOMBRES DE LOS METODOS
 *
 * En vez de los nombres genericos que usa Laravel para un CRUD
 * (index, show, store, update, destroy), cada metodo se llama segun
 * lo que hace:
 *
 *   listProducts()   -> listar todos      GET    /productos
 *   getProduct()     -> ver uno           GET    /productos/3
 *   createProduct()  -> crear             POST   /productos
 *   updateProduct()  -> modificar         PATCH  /productos/3
 *   deleteProduct()  -> borrar            DELETE /productos/3
 *   sellProduct()    -> vender            POST   /productos/3/vender
 *
 * ------------------------------------------------------------------
 * ?DONDE VALIDO CADA COSA? (la pregunta que siempre aparece)
 *
 *   En el CONTROLLER -> que los datos VENGAN y tengan la forma
 *                       correcta: "falta el nombre", "el precio no
 *                       es un numero".
 *
 *   En el SERVICE    -> las reglas del sistema: "ese nombre ya
 *                       existe", "no hay stock suficiente".
 *
 * Regla practica: si para responder hay que ir a buscar datos,
 * es del service.
 *
 * requireLogin(), requireAdmin() y getJsonBody() estan en
 * core/helpers.php.
 * ==================================================================
 */
class ProductController
{
    private ProductService $productService;

    /**
     * El controller USA un service, y el service usa un repository.
     * Un objeto que tiene adentro otro objeto se llama COMPOSICION.
     */
    public function __construct()
    {
        $this->productService = new ProductService();
    }

    /**
     * GET /productos
     * GET /productos?categoria=audio
     *
     * Esta ruta no recibe un ID porque pide la coleccion completa.
     */
    public function listProducts()
    {
        // Lo que viene despues del "?" esta en $_GET.
        $category = $_GET['categoria'] ?? null;

        $products = $this->productService->getAll($category);

        Response::success($products);
    }

    /** GET /productos/3 */
    public function getProduct($id)
    {
        $id = $this->validateId($id);

        $product = $this->productService->getById($id);

        Response::success($product);
    }

    /**
     * POST /productos   (hay que estar logueado)
     */
    public function createProduct()
    {
        requireLogin();

        $data = getJsonBody();

        //Aca se puede ver la estructura de los datos que manda el cliente, y se pueden validar. Si algo no esta bien, respondemos con error 400 y un mensaje.
        $name        = $data['nombre'] ?? null;
        $description = $data['descripcion'] ?? '';
        $price       = $data['precio'] ?? null;
        $stock       = $data['stock'] ?? null;
        $category    = $data['categoria'] ?? null;

        // ---- VALIDACION ------------------------------------------
        // Solo miramos que los datos esten y sean lo que decimos.
        $errors = [];

        if (!is_string($name) || strlen(trim($name)) < 3) {
            $errors[] = 'El nombre tiene que tener al menos 3 letras.';
        }

        if (!is_string($description)) {
            $errors[] = 'La descripcion tiene que ser texto.';
        }

        if (!is_numeric($price) || $price < 0) {
            $errors[] = 'El precio tiene que ser un numero mayor o igual a 0.';
        }

        if (filter_var($stock, FILTER_VALIDATE_INT) === false || $stock < 0) {
            $errors[] = 'El stock tiene que ser un entero mayor o igual a 0.';
        }

        if (!is_string($category) || trim($category) === '') {
            $errors[] = 'Falta la categoria.';
        }

        if (count($errors) > 0) {
            Response::error('Revisa los datos.', 400, $errors);
        }

        // ---- Y ACA LE PASAMOS LA PELOTA AL SERVICE ---------------
        $product = $this->productService->create(
            trim($name),
            trim($description),
            (float) $price,
            (int) $stock,
            trim($category)
        );

        Response::success($product, 'Producto creado.', 201);
    }

    /**
     * PATCH /productos/3   (hay que estar logueado)
     * Se mandan solo los campos que se quieren cambiar.
     */
    public function updateProduct($id)
    {
        requireLogin();

        $id = $this->validateId($id);
        $data = getJsonBody();

        $errors = [];
        // Validamos solo los campos que vienen, y los normalizamos. En versiones avanzadas esto se conoce como "DTO" (Data Transfer Object) y se hace con una clase aparte. Acá lo hacemos rapido en el controller.
        $allowedFields = ['nombre', 'descripcion', 'precio', 'stock', 'categoria'];
        $receivedFields = array_intersect(array_keys($data), $allowedFields);

        if (count($receivedFields) === 0) {
            $errors[] = 'No mandaste ningun campo valido para cambiar.';
        }

        // Validamos los campos recibidos
        if (array_key_exists('nombre', $data)
            && (!is_string($data['nombre']) || strlen(trim($data['nombre'])) < 3)) {
            $errors[] = 'El nombre tiene que tener al menos 3 letras.';
        }

        if (array_key_exists('descripcion', $data) && !is_string($data['descripcion'])) {
            $errors[] = 'La descripcion tiene que ser texto.';
        }

        if (array_key_exists('precio', $data)
            && (!is_numeric($data['precio']) || $data['precio'] < 0)) {
            $errors[] = 'El precio tiene que ser un numero mayor o igual a 0.';
        }

        if (array_key_exists('stock', $data)
            && (filter_var($data['stock'], FILTER_VALIDATE_INT) === false || $data['stock'] < 0)) {
            $errors[] = 'El stock tiene que ser un entero mayor o igual a 0.';
        }

        if (array_key_exists('categoria', $data)
            && (!is_string($data['categoria']) || trim($data['categoria']) === '')) {
            $errors[] = 'La categoria no puede estar vacia.';
        }

        if (count($errors) > 0) {
            Response::error('Revisa los datos.', 400, $errors);
        }

        // Despues de validar, normalizamos y tipamos solamente lo recibido.
        if (array_key_exists('nombre', $data)) {
            $data['nombre'] = trim($data['nombre']);
        }

        if (array_key_exists('descripcion', $data)) {
            $data['descripcion'] = trim($data['descripcion']);
        }

        if (array_key_exists('precio', $data)) {
            $data['precio'] = (float) $data['precio'];
        }

        if (array_key_exists('stock', $data)) {
            $data['stock'] = (int) $data['stock'];
        }

        if (array_key_exists('categoria', $data)) {
            $data['categoria'] = trim($data['categoria']);
        }

        $product = $this->productService->update($id, $data);

        Response::success($product, 'Producto actualizado.');
    }

    /**
     * DELETE /productos/3   (solo administradores)
     */
    public function deleteProduct($id)
    {
        // Aca pedimos ADMIN: no alcanza con estar logueado.
        requireAdmin();

        $id = $this->validateId($id);

        $this->productService->delete($id);

        Response::success(null, 'Producto eliminado.');
    }

    /**
     * POST /productos/3/vender   (hay que estar logueado)
     * Recibe: { "cantidad": 2 }
     *
     * Fijate que el controller no sabe NADA de como se vende:
     * no descuenta stock ni controla nada. Solo pasa el pedido.
     */
    public function sellProduct($id)
    {
        requireLogin();

        $id = $this->validateId($id);
        $data = getJsonBody();

        $quantity = $data['cantidad'] ?? 1;

        if (filter_var($quantity, FILTER_VALIDATE_INT) === false || $quantity < 1) {
            Response::error('La cantidad tiene que ser un entero mayor o igual a 1.', 400);
        }

        $sale = $this->productService->sell($id, (int) $quantity);

        Response::success($sale, 'Venta registrada.');
    }

    /**
     * Valida los IDs de la URL sin crear todavia una clase Validator.
     * Devuelve el ID como int para que el service reciba un tipo conocido.
     */
    private function validateId($id): int
    {
        if (filter_var($id, FILTER_VALIDATE_INT) === false || $id < 1) {
            Response::error('El ID del producto no es valido.', 400);
        }

        return (int) $id;
    }
}
