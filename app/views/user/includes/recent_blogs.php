<?php

$rss_url  = "http://myserver.com/PHP/vs950/blog/feed/";
$blog_home = "http://myserver.com/PHP/vs950/public/blog/";

$rss = @simplexml_load_file($rss_url);

if ($rss && isset($rss->channel->item)):

    $count = 0;

    foreach ($rss->channel->item as $item):

        if ($count >= 4) break;

        $title       = htmlspecialchars((string)$item->title);
        $link        = htmlspecialchars((string)$item->link);
        $date        = date("d M Y", strtotime((string)$item->pubDate));
        $description = substr(strip_tags((string)$item->description), 0, 80);

        // Extract featured image
        $image = '';
        $media = $item->children('media', true);
        if (!empty($media->content))
            $image = (string)$media->content->attributes()->url;
        if (!$image && !empty($item->enclosure))
            $image = (string)$item->enclosure->attributes()->url;
        if (!$image)
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', (string)$item->description, $m))
                $image = $m[1];

?>
<div class="col-4">
    <a href="<?= $link ?>" target="_blank" class="text-decoration-none">
        <div class="bc">

            <?php if ($image): ?>
            <div class="bc-img">
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= $title ?>">
            </div>
            <?php endif; ?>

            <div class="bc-body">
                <p class="bc-title"><?= $title ?></p>
                <p class="bc-desc"><?= $description ?>...</p>
                <div class="bc-foot">
                    <small><?= $date ?></small>
                    <small class="text-danger fw-semibold">Read more →</small>
                </div>
            </div>

        </div>
    </a>
</div>
<?php
        $count++;
    endforeach;

endif;
?>

