<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
Use Nette\Security\Passwords;

class SettingsPresenter extends Nette\Application\UI\Presenter {

    private Nette\Database\Explorer $database;
    private $passwords;

    public function __construct(Nette\Database\Explorer $database, Passwords $passwords)
    {
        $this->database = $database; 
        $this->passwords = $passwords;
    }

    protected function createComponentPasswordForm(): Form
    {
        $form = new Form;

        $form->addPassword('password', 'Staré heslo: ')
            ->setRequired("Prosím vyplňte své heslo.");
        $form->addPassword('passwordNew', 'Nové heslo: ')
            ->setRequired("Prosím vyplňte své heslo znovu pro ověření.");
        $form->addSubmit('send', 'Vytvořit účet');

        $form->onSuccess[] = [$this, 'passwordFormSucceeded'];
        return $form;
    }

    public function passwordFormSucceeded(Form $form, $values): void 
    {
        $user = $this->getUser();
        $userId = $user->getIdentity()->id;
        $userPassword = $this->database->fetchField('SELECT password FROM users WHERE id = ?', $userId);

        #$values->password == $userPassword
        if ($this->passwords->verify($values->password,  $userPassword))
        {
            $this->database->query('UPDATE users SET', [
                'password' => $this->passwords->hash($values->passwordNew)
            ], 'WHERE id = ?', $userId);
            $user->logout(true);
            $this->flashMessage("Heslo bylo úspěšně změněno", 'success');
		    $this->redirect('Homepage:');

        } else {
            $this->flashMessage("Staré heslo se neshoduje", 'wrong');
		    $this->redirect('this');
        }
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
        $user->logout(true);
        $this->flashMessage("Uživatelské jméno bylo změněno", 'success');
		$this->redirect('Homepage:');
    }

    public function actionPassword(): void
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in');
		}
    }

    public function actionUser(): void
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in');
		}
    }

    public function actionUsername(): void
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in');
		}
    }
    
}