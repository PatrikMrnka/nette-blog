<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

class PostPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;
    
    public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
	}

	public function renderShow(int $postId): void
	{
		$post = $this->database->table('posts')->get($postId);
		if (!$post) {
			$this->error('Stránka nebyla nalezena');
		}

		$this->template->post = $post;
		$this->template->comments = $post->related('comments')->order('created_at');
	}

	protected function createComponentCommentForm(): Form
	{
		$form = new Form; // means Nette\Application\UI\Form
		$user = $this->getUser();

		if (!$user->isLoggedIn()) {
			$form->addText('name', 'Jméno:')
			->setRequired();
			$form->addEmail('email', 'E-mail:');
		}

		$form->addTextArea('content', 'Komentář:')
			->setRequired();

		$form->addSubmit('send', 'Publikovat komentář');

		$form->onSuccess[] = [$this, 'commentFormSucceeded'];

		return $form;
	}

	public function commentFormSucceeded(\stdClass $values): void
	{
		$postId = $this->getParameter('postId');
		$user = $this->getUser();

		if ($user->isLoggedIn()) {
			$this->database->table('comments')->insert([
				'post_id' => $postId,
				'name' => $user->getIdentity()->name,
				'email' => $user->getIdentity()->email,
				'content' => $values->content,
			]);
		} else {
			$this->database->table('comments')->insert([
				'post_id' => $postId,
				'name' => $values->name,
				'email' => $values->email,
				'content' => $values->content,
			]);
		}

		$this->flashMessage('Děkuji za komentář', 'success');
		$this->redirect('this');
	}

	protected function createComponentPostForm(): Form
	{
		$form = new Form;
		
		$form->addText('title', 'Titulek: ')
			->setRequired();
		$form->addTextArea('content', 'Obsah: ')
			->setRequired();

		$form->addSubmit('send', 'Uložit a publikovat');
		$form->onSuccess[] = [$this, 'postFormSucceeded'];

		return $form;
	}

	public function postFormSucceeded(Form $form, $values): void
	{
		$postId = $this->getParameter('postId');
        $user = $this->getUser();

        if ($postId) {
            $post = $this->database->table('posts')->get($postId);
            $post->update($values);
		} else {
			$post = $this->database->table('posts')->insert([
				'title' => $values->title,
				'content' => $values->content,
				'author' => $user->getIdentity()->name
			]);
		}

		$this->flashMessage("Příspěvek byl úspěšně publikován.", 'success');
		$this->redirect('show', $post->id);
	}

	public function actionCreate(): void
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in');
		}
	}


	public function actionEdit(int $postId): void
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in');
		}	
		
		$post = $this->database->table('posts')->get($postId);
		$user = $this->getUser();

		if (!$post) {
			$this->error('Příspěvek nebyl nalezen');
		} else if ($post->author != $user->getIdentity()->name && $user->getIdentity()->name != "admin") {
			$this->flashMessage("Nemáš oprávnění upravit tento příspěvek");
			$this->redirect('show', $postId);
		}
		$this['postForm']->setDefaults($post->toArray());
	}
}