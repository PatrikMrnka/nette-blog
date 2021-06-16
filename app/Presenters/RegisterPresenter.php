<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
Use Nette\Security\Passwords;

class RegisterPresenter extends Nette\Application\UI\Presenter 
{
    private Nette\Database\Explorer $database;
    private Passwords $passwords;

    public function __construct(Nette\Database\Explorer $database, Passwords $passwords)
    {
        $this->database = $database; 
        $this->passwords = $passwords;
    }

    protected function createComponentRegisterForm(): Form
    {
        $form = new Form;

        $form->addText('username', 'Uživatelské jméno: ')
            ->setRequired("Prosím vyplňte své uživatelské jméno.");
        $form->addEmail('email', 'E-mailová adresa: ')
            ->setRequired("Prosím vyplňte svoji e-mailovou adresu.");
        $form->addPassword('password', 'Heslo: ')
            ->setRequired("Prosím vyplňte své heslo.");
        $form->addPassword('passwordAgain', 'Heslo znovu: ')
            ->setRequired("Prosím vyplňte své heslo znovu pro ověření.");
        $form->addSubmit('send', 'Vytvořit účet');

        $form->onSuccess[] = [$this, 'registerFormSucceeded'];
        return $form;
    }

    public function registerFormSucceeded(\stdClass $values): void
    {
        $users = $this->database->table('users');

        if (count($users->where('username', $values->username)) != 0) {
            $this->flashMessage("Uživatelské jméno je již používáno", "wrong");
            $this->redirect("this");
        } else if (count($users->where('email', $values->email)) != 0) {
            $this->flashMessage("E-mailová adresa už je používána", "wrong");
            $this->redirect("this");
        } else if ($values->password != $values->passwordAgain) {
            $this->flashmessage("Hesla se neshodují", "wrong");
            $this->redirect("this");
        } else {
            $users->insert([
                'username' => $values->username,
                'email' => $values->email,
                'password' => $this->passwords->hash($values->password),
                'role' => "Member",
            ]);
            $this->flashMessage("Úspěšně jste se zaregistrovali.", "success");
            $this->redirect('Homepage:');
        }
    }
}