{if $blog_articles_newest}
    <h3>{_t("blog.label_latest_posts")}</h3>
    {foreach from=$blog_articles_newest key=article_id item=article}

        <div class="media">
            {if $article.pic_preview}
                <div class="media-left">
                    <a href="{$lang_url}{$blog.baseUrl}{$article.url}/">
                        <img class="media-object" src="{$article.pic_preview}" alt="{$article.title}" width="64">
                    </a>
                </div>
            {/if}
            <div class="media-body">
                <h4 style="margin-top:0;"><a href="{$lang_url}{$blog.baseUrl}{$article.url}/">{$article.title}</a></h4>
                <p class="text-muted">
                    <small class="text-muted pull-right"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span>
                        <a href="{$lang_url}{$blog.baseUrl}{$article.url}/#comments" class="text-muted">{$article.comments} {_t("blog.comments")}</a>
                    </small>
                    <small class="text-muted">{$article.date}</small><br>
                </p>
            </div>
        </div>

    {/foreach}

    <p>&nbsp;</p>
{/if}