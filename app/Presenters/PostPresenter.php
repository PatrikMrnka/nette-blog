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

		$user = $this->getUser();
		$this->template->user = $user;

		// $likes = $this->database->table('likes');
		// $liked = $likes->where('userId', $user->getIdentity()->id)->where('postId', $postId)->fetch();
		// $this->template->liked = $liked;
		$user_likes = $this->database->table('user_likes');
        $user_liked_post = $user_likes->where('user_id', $user->getIdentity()->id)->where('post_id', $postId)->fetch();
        $this->template->user_liked_post = $user_liked_post;

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

	public function actionLike($postId, bool $like): void
    {
        $user = $this->getUser();
        $posts = $this->database->table('posts');
        $user_likes = $this->database->table('user_likes');

        if (!$user->isLoggedIn()) {
            $this->flashMessage("Musíš být přihlášený, aby si mohl ohodnotit příspěvek", 'wrong');
            $this->redirect('show', $postId);
        }

        $row = $posts->get($postId);

        $user_liked_post = $user_likes->where('user_id', $user->getIdentity()->id)->where('post_id', $postId)->fetch();

        if (!$user_liked_post) {
            if ($like)
            {
				$this->database->query('INSERT INTO user_likes', [
					'post_id' => $postId,
                    'user_id' => $user->getIdentity()->id,
                    'user_like' => true,
                    'user_dislike' => false
				]);

				$this->database->query('UPDATE posts SET', [
					'likes' => $row->likes+1
				], 'WHERE id = ?', $postId);
            }
            else
            {
				$this->database->query('INSERT INTO user_likes', [
					'post_id' => $postId,
                    'user_id' => $user->getIdentity()->id,
                    'user_like' => false,
                    'user_dislike' => true
				]);

				$this->database->query('UPDATE posts SET', [
					'dislikes' => $row->dislikes+1
				], 'WHERE id = ?', $postId);
            }
            $this->redirect('show', $postId);
        }

        if ($like)
        {
            if (!$user_liked_post->user_like)
            {
				$this->database->query('UPDATE posts SET', [
					'likes' => $row->likes+1
				], 'WHERE id = ?', $postId);

				$this->database->query('UPDATE user_likes SET', [
					'user_like' => true
				], 'WHERE user_id = ? AND post_id = ?', $user->getIdentity()->id, $postId);

                if ($user_liked_post->user_dislike)
                {
					$this->database->query('UPDATE posts SET', [
						'dislikes' => $row->dislikes-1
					], 'WHERE id = ?', $postId);

					$this->database->query('UPDATE user_likes SET', [
						'user_dislike' => false
					], 'WHERE user_id = ? AND post_id = ?', $user->getIdentity()->id, $postId);
                }
            }
            else
            {
				$this->database->query('UPDATE posts SET', [
					'likes' => $row->likes-1
				], 'WHERE id = ?', $postId);

				$this->database->query('UPDATE user_likes SET', [
					'user_like' => false
				], 'WHERE user_id = ? AND post_id = ?', $user->getIdentity()->id, $postId);
            }
        }
        else
        {
            if (!$user_liked_post->user_dislike)
            {
				$this->database->query('UPDATE posts SET', [
					'dislikes' => $row->dislikes+1
				], 'WHERE id = ?', $postId);

				$this->database->query('UPDATE user_likes SET', [
					'user_dislike' => true
				], 'WHERE user_id = ? AND post_id = ?', $user->getIdentity()->id, $postId);

                // if user already liked the post delete the like
                if ($user_liked_post->user_like)
                {
					$this->database->query('UPDATE posts SET', [
						'likes' => $row->likes-1
					], 'WHERE id = ?', $postId);

					$this->database->query('UPDATE user_likes SET', [
						'user_like' => false
					], 'WHERE user_id = ? AND post_id = ?', $user->getIdentity()->id, $postId);
                }
            }
            else
            {
				$this->database->query('UPDATE posts SET', [
					'dislikes' => $row->dislikes-1
				], 'WHERE id = ?', $postId);

				$this->database->query('UPDATE user_likes SET', [
					'user_dislike' => false
				], 'WHERE user_id = ? AND post_id = ?', $user->getIdentity()->id, $postId);
            }
        }

        $this->redirect('show', $postId);
    }
}
