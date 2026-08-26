<?php

/**
 * CLASE REPOSITORY (repositorio)  -  clase PADRE
 * ==================================================================
 * ?QUE ES UN REPOSITORY?
 * Es el objeto encargado de buscar y guardar datos. Es la UNICA parte
 * del programa que sabe de donde salen. El resto del codigo le pide
 * "dame los productos" y no se entera de como estan guardados.
 *
 * Vas a ver el mismo nombre en Laravel y en Symfony (ProductRepository).
 * Tambien vas a escuchar "DAO" (Data Access Object), que es el nombre
 * que le pone el mundo Java a la misma idea.
 *
 * ------------------------------------------------------------------
 * ACA APARECE LA HERENCIA.
 *
 * UserRepository y ProductRepository necesitan las dos lo mismo: una
 * conexion a la base para poder hacer sus consultas. En vez de que
 * cada una abra la suya, lo escribimos UNA sola vez aca, en la clase
 * padre, y las dos clases hijas lo heredan:
 *
 *     class UserRepository extends Repository { ... }
 *
 * "extends" = "hereda de". La clase hija recibe gratis $this->db, ya
 * conectado, sin tener que escribir un constructor propio.
 *
 * ------------------------------------------------------------------
 * OJO: esta clase es abstracta. PHP no permite hacer
 * "new Repository()". Solo existe para que las hijas hereden
 * la conexion y despues busquen una tabla concreta.
 * ==================================================================
 */
abstract class Repository
{
    /**
     * protected = la ven esta clase Y sus hijas, pero nadie de afuera.
     * (private seria solo esta clase; public seria todo el mundo).
     */
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }
}
