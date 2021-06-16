<?php

namespace App;

use Nette;
use Nette\Security\SimpleIdentity;
use Nette\Security\Passwords;

class Authenticator implements Nette\Security\Authenticator
{
    private $database;
    private $passwords;

    public function __construct(Nette\Database\Explorer $database, Passwords $passwords) 
    {
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

        if (!$this->passwords->verify($password, $row->password)) {
			throw new Nette\Security\AuthenticationException('Neplatné heslo.');
		}

        return new SimpleIdentity (
            $row->id,
            $row->role,
            ['name' => $row->username, 'email' => $row->email]
        );
    }   
}