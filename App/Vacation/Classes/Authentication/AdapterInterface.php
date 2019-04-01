<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 30.3.2019.
 * Time: 00.07
 */
namespace App\Vacation\Authentication;

interface AdapterInterface {

    public function authenticate();

    /**
     * Returns true if and only if an identity is available from storage
     *
     * @return bool
     */
    public function hasIdentity();

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return mixed|null
     */
    public function getIdentity();

    /**
     * Clears the identity from persistent storage
     *
     * @return void
     */
    public function clearIdentity();

}