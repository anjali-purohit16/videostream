<?php

class WishlistController extends BaseController
{
    public function wishlist_toggle(): void
    {
        $userId  = $this->requireActiveUser(true);
        $videoId = (int)($_GET['id'] ?? 0);
        $result = (new UserModel())->toggleWishlist($userId, $videoId);
        WsPublisher::push('wishlist', ['audience' => 'user', 'user_id' => $userId]);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function remove_wishlist(): void
    {
        $userId  = $this->requireActiveUser();
        $videoId = (int)($_POST['video_id'] ?? 0);
        (new UserModel())->removeWishlistItem($userId, $videoId);
        WsPublisher::push('wishlist', ['audience' => 'user', 'user_id' => $userId]);
        $this->redirect(BASE_URL . 'watchlist');
    }
}
