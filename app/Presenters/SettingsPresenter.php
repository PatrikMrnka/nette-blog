<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

class SettingsPresenter extends Nette\Application\UI\Presenter {

    private Nette\Database\Explorer $database;

    public function __construct(Nette\Database\Explorer $database)
    {
        $this->database = $database; 
    }

    protected function createComponentSettingsPasswordForm(): Form
    {
        $form = new Form;

        $form->addPassword('password', 'Staré heslo: ')
            ->setRequired("Prosím vyplňte své heslo.");
        $form->addPassword('passwordNew', 'Nové heslo: ')
            ->setRequired("Prosím vyplňte své heslo znovu pro ověření.");
        $form->addSubmit('send', 'Vytvořit účet');

        $form->onSuccess[] = [$this, 'registerFormSucceeded'];
        return $form;
    }

    public function settingsPasswordSucceeded(): void 
    {

    }

    protected function createComponentUsernameForm(): Form
    {
        $form = new Form;

        $form->addText('usernameNew', 'Nové uživatelské jméno: ')
            ->setRequired("Prosím vyplňte své nové uživatelské jméno.");
        $form->addSubmit('send', 'Změnit uživatelské jméno');

        $form->onSuccess[] = [$this, 'usernameFormSucceeded'];
        return $form;
    }

    public function usernameFormSucceeded(Form $form, $values): void
    {
        $user = $this->getUser();
        $username = $user->getIdentity()->name;
        $this->database->query('UPDATE users SET', [
            'username' => $values->usernameNew
        ], 'WHERE id = ?', $user->getIdentity()->id);

        $this->database->query('UPDATE posts SET', [
            'author' => $values->usernameNew
        ], 'WHERE author = ?', $username);

        $this->database->query('UPDATE comments SET', [
            'name' => $values->usernameNew
        ], 'WHERE name = ?', $username);

        $username = $values->usernameNew;
        $this->flashMessage("Uživatelské jméno bylo změněno", 'success');
		$this->redirect('this');
    }
}