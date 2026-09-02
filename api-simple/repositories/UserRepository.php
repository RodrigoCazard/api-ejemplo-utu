<?php

/**
 * REPOSITORIO DE USUARIOS  (clase HIJA de Repository)
 * ------------------------------------------------------------------
 * Es la unica parte del programa que busca y guarda usuarios en la
 * tabla `usuarios`.
 *
 * (Las columnas de la base van en espanol, igual que los campos del
 * JSON que devuelve la API: son los datos, no el codigo.)
 */
class UserRepository extends Repository
{
    /**
     * Busca un usuario por email. Se usa en el LOGIN.
     * Devuelve un objeto User, o null si no lo encuentra.
     */
    public function findByEmail($email)
    {
        $sql = 'SELECT * FROM usuarios WHERE email = :email';

        $query = $this->db->prepare($sql);
        $query->execute([':email' => trim($email)]);
        $row = $query->fetch();

        return $row === false ? null : $this->buildUser($row);
    }

    /** Busca un usuario por su id. */
    public function findById($id)
    {
        $sql = 'SELECT * FROM usuarios WHERE id = :id';

        $query = $this->db->prepare($sql);
        $query->execute([':id' => $id]);
        $row = $query->fetch();

        return $row === false ? null : $this->buildUser($row);
    }

    /**
     * Crea un usuario nuevo (registro).
     * Recibe el hash de la contrasena ya generado por AuthService.
     */
    public function create($name, $email, $passwordHash)
    {
        $sql = "INSERT INTO usuarios (nombre, email, clave_hash, rol, activo)
                VALUES (:nombre, :email, :clave_hash, 'usuario', 1)";

        $query = $this->db->prepare($sql);
        $query->execute([
            ':nombre'     => $name,
            ':email'      => $email,
            ':clave_hash' => $passwordHash,
        ]);

        // lastInsertId() devuelve el id que le puso la base.

       $id = (int) $this->db->lastInsertId();

        $registroCreado = $this->findById($id);

        return $registroCreado;
    }

    /**
     * Convierte una fila de la base (arreglo) en un OBJETO User.
     *
     * Este pasito es el que separa "los datos crudos de la tabla" de
     * "un objeto con el que se puede trabajar".
     */
    private function buildUser($row)
    {
        return new User(
            $row['id'],
            $row['nombre'],
            $row['email'],
            $row['clave_hash'],
            $row['rol'],
            (bool) $row['activo']
        );
    }
}
