<?php

namespace App;

use Nette;
use Nette\Security\SimpleIdentity;

class Authenticator implements Nette\Security\Authenticator
{
    private $database;
    private $passwords;

    public function __construct(
        Nette\Database\Explorer $database,
        Nette\Security\Passwords $passwords
    ) {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    public function authenticate(string $username, string $password): SimpleIdentity
    {
        $row = $this->database->table('users')
            ->where('username', $username)
            ->fetch();
        
        if (!$row) {
            throw new Nette\Security\AuthenticationException('Uživatel nenalezen.');
        }

        if ($password != $row->password) {
            throw new Nette\Security\AuthenticationException("Nesprávné heslo.");
        }

        return new SimpleIdentity (
            $row->id,
            $row->role,
            ['name' => $row->username, 'email' => $row->email]
        );
    }   
}