<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Arrays;

class PostPresenter extends Nette\Application\UI\Presenter
{
	private Nette\Database\Explorer $database;
	private $arrayLike = [];
    
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

	public function renderDelete(int $postId): void 
	{
		$post = $this->database->table('posts')->get($postId);
		$this->template->post = $post;
		$this->template->posts = $this->database->table('posts')->get($postId);
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

	protected function createComponentDeleteForm(): Form
	{
		$form = new Form;
		$postId = $this->getParameter('postId');

		$form->addSubmit('yes', 'ANO')
			->onClick[] = [$this, 'deleteFormSucceeded'];

		$form->addSubmit('no', 'NE')
			->onClick[] = [$this, 'deleteFormNotSucceeded'];

		return $form;
	}

	public function deleteFormSucceeded(Nette\Forms\Controls\Button $button, $values)
	{
		$postId = $this->getParameter('postId');

		$this->database->query('DELETE FROM posts WHERE id = ?', $postId);

		// $post = $this->database->query('DELETE FROM users WHERE id = ?', $postId);
		$this->flashMessage("Příspěvek byl úspěšně smazán", 'success');
		$this->redirect('Homepage:');
	}

	public function deleteFormNotSucceeded(Nette\Forms\Controls\Button $button)
	{
		$this->redirect('Homepage:');
	}

	protected function createComponentRateForm(): Form
	{
		$form = new Form;
		$postId = $this->getParameter('postId');

		$form->addSubmit('like', 'LIKE')
			->onClick[] = [$this, 'rateFormLike'];

		$form->addSubmit('dislike', 'DISLIKE')
			->onClick[] = [$this, 'rateFormDislike'];

		return $form;
	}

	public function rateFormLike(Nette\Forms\Controls\Button $button, $values)
	{
		$user = $this->getUser();
		$postId = $this->getParameter('postId');
		$userId = $user->getIdentity()->id;
		// Arrays::insertAfter($this->arrayLike, 'a', [$userId => $userId]);
		// $valueLike = Arrays::get($this->arrayLike, $userId);

		// if ($this->database->table('posts')->get('like') == 0 || $valueLike == $userId ) {
		// 	$this->database->query('UPDATE posts SET', [
		// 		'like+=' => 1,
		// 	], 'WHERE id = ?', $postId);
		// 	$this->flashMessage("Děkujeme za hlas (like)", 'success');
		// 	echo $valueLike;
		// 	echo $userId;
		// } else if ($valueLike == $userId) {
		// 	$this->flashMessage("Už si příspěvek ohodnotil.", "wrong");
		// }
		// $this->database->query('UPDATE posts SET', [
		// 	'like+=' => 1,
		// ], 'WHERE id = ?', $postId);
		// echo $value;
		// if ($this->database->table('posts')->get('like') == 0) {
		// 	Arrays::insertAfter($this->arrayLike, 'a', [$userId => $userId]);
		// 	$valueLike = Arrays::get($this->arrayLike, $userId);
		// 	$this->flashMessage("Děkujeme za hlas (like)", 'success');
		// 	echo $valueLike;

		// } else if ($valueLike == $userId) {
		// 	$this->flashMessage("Už si příspěvek ohodnotil.", "wrong");
		// 	$this->redirect('show', $postId);
		// } 
	}

	public function rateFormDislike(Nette\Forms\Controls\Button $button, $values)
	{
		$postId = $this->getParameter('postId');
		
		$this->database->query('UPDATE posts SET', [
			'dislike+=' => 1,
		], 'WHERE id = ?', $postId);

		// Arrays::insertAfter($arrayLike, 'a', [$userId => $userId]);
		// $value = Arrays::get($arrayLike, $userId);
		// echo $value;

		// $post = $this->database->query('DELETE FROM users WHERE id = ?', $postId);
		$this->flashMessage("Děkujeme za hlas (dislike)", 'success');
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

	public function actionDelete(int $postId): void
	{	
		$post = $this->database->table('posts')->get($postId);
		$user = $this->getUser();

		if (!$post) {
			$this->error('Příspěvek nebyl nalezen');
		} else if ($post->author != $user->getIdentity()->name && $user->getIdentity()->name != "admin") {
			$this->flashMessage("Nemáš oprávnění smazat tento příspěvek");
			$this->redirect('show', $postId);
		}
	}

	public function actionShow(int $postId, $valueLike): void{
		$post = $this->database->table('posts')->get($postId);
		$user = $this->getUser();
		$userId = $user->getIdentity()->id;

	}
}