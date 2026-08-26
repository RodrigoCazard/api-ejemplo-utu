<?php

/**
 * CLASE USER (usuario)
 * ==================================================================
 * Esta es la POO mas clasica que hay, la del primer dia:
 *
 *   - PROPIEDADES privadas  -> los datos del objeto
 *   - CONSTRUCTOR           -> se ejecuta al hacer "new User(...)"
 *   - GETTERS               -> metodos para leer esos datos desde afuera
 *   - METODOS propios       -> lo que el objeto sabe hacer
 *
 * ?Por que las propiedades son PRIVADAS?
 * Porque asi nadie de afuera puede escribir cualquier cosa adentro del
 * objeto. Si $passwordHash fuera publica, alguien podria hacer:
 *
 *     $user->passwordHash = '123';     // !desastre!
 *
 * Con private, la unica forma de trabajar con la contrasena es a traves
 * de los metodos que nosotros escribimos. A eso se le llama
 * ENCAPSULAMIENTO: el objeto protege sus propios datos.
 * ==================================================================
 */
class User
{
    // Las propiedades: que datos tiene un usuario.
    private int $id;
    private string $name;
    private string $email;
    private string $passwordHash;   // hash de la contrasena
    private string $role;
    private bool $active;

    /**
     * CONSTRUCTOR
     * Se ejecuta solo cuando hacemos:  new User(1, 'Ana', ...)
     * $this se refiere al objeto que se esta creando.
     */
    public function __construct($id, $name, $email, $passwordHash, $role, $active)
    {
        $this->id           = $id;
        $this->name         = $name;
        $this->email        = $email;
        $this->passwordHash = $passwordHash;
        $this->role         = $role;
        $this->active       = $active;
    }

    // ------------------------------------------------------------------
    // GETTERS: dejan LEER los datos, pero no modificarlos.
    // ------------------------------------------------------------------

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function isActive()
    {
        return $this->active;
    }

    /**
     * Fijate que NO hay un getPasswordHash().
     * El hash entra al objeto y no sale nunca mas.
     */

    // ------------------------------------------------------------------
    // METODOS: lo que el usuario sabe hacer.
    // ------------------------------------------------------------------

    /**
     * ?Es correcta esta contrasena?
     *
     * password_verify() toma lo que escribio la persona y comprueba si
     * corresponde al hash guardado. Nunca comparamos con == y el hash
     * no se puede revertir: funciona en un solo sentido.
     */
    public function checkPassword($plainPassword)
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * Convierte el objeto en un arreglo, para poder mandarlo como JSON.
     *
     * !IMPORTANTE! Aca NO va la contrasena. Una API nunca devuelve
     * contrasenas, ni siquiera sus hashes.
     *
     * (Los nombres de los campos van en espanol porque son el "contrato"
     * de nuestra API: es lo que ven los que la consumen.)
     */
    public function toArray()
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->name,
            'email'  => $this->email,
            'rol'    => $this->role,
            'activo' => $this->active,
        ];
    }
}
