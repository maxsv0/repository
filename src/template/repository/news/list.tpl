
<h3>{_t("blog.label_latest_posts")}</h3>
{foreach from=$blog_articles key=article_id item=article}
    <div class="media">
        {if $article.pic_preview}
            <div class="media-left">
                <a href="{$lang_url}{$blog.baseUrl}{$article.url}/">
                    <img class="media-object" src="{$article.pic_preview}" alt="{$article.title}" width="120">
                </a>
            </div>
        {/if}
        <div class="media-body">
            <h4 class="media-heading"><a href="{$lang_url}{$blog.baseUrl}{$article.url}/">{$article.title}</a></h4>
            <p class="text-muted small">
                <a href="{$lang_url}{$blog.baseUrl}?{$blog.authorUrlParam}={$article.email}">{$article.email}</a>
                {_t("blog.posted_on")} {$article.date}
            </p>
            {if $article.description}
                <p>{$article.description}</p>
            {/if}
        </div>
    </div>
{/foreach}

<p class="clearfix"></p>
