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

<style>
/* Grid wrapper — equal gutters */
#blog-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap:5px;
}

/* Card — fixed equal height */
.bc {
    background: #1a1a1a;
    border-radius: 10px;
    overflow: hidden;
    height: 210px;
    width: 15vw;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s, box-shadow 0.2s;
}
.bc:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}

/* Image strip */
.bc-img {
    height: 95px;
    flex-shrink: 0;
    overflow: hidden;
}
.bc-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Body */
.bc-body {
    padding: 10px 12px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
}
.bc-title {
    font-size: 0.83rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 3px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.bc-desc {
    font-size: 0.73rem;
    color: #999;
    margin: 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.bc-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.71rem;
    color: #666;
    margin-top: 5px;
}
</style>