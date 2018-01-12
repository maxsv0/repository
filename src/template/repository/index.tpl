{include file="$themeDefaultPath/widget/header.tpl" themePath=$themeDefaultPath}

<div class="container top-menu">
    <div class="row">
        {include file="$themeDefaultPath/widget/menu-top.tpl"}
    </div>
</div>

<div class="container content-block">
    <div class="row">

        <div class="col-md-12">

            <!-- page document -->
            {if $document.name}
                <h1>{$document.name}</h1>
            {/if}
            {if $document.text}
                {$document.text}
            {/if}

            {include file="$themeDefaultPath/widget/messages.tpl"}


            <table class="table table-hover">

                <tr>
                    <th class="col-sm-1">Preview</th>
                    <th class="col-sm-2">Title</th>
                    <th class="col-sm-1">Version</th>
                    <th class="col-sm-1">Release date</th>
                    <th class="col-sm-4">Description</th>
                    <th class="col-sm-1">Source</td>
                    <th class="col-sm-2">Files</th>
                </tr>

                {foreach from=$repository_list item=module}

                    <tr class="module-block">
                        <td><a href="{$module.preview}" rel="fancybox"><img src="{$module.preview}" class="img-responsive"></a></td>
                        <td>{$module.title}</td>
                        <td>{$module.version}</td>
                        <td>{$module.date|substr:0:11}</td>
                        <td>{$module.description}</td>
                        <td>{$module.source}</td>
                        <td>
                            {basename($module.archive)}
                            <span class="text-muted">{$module.size|filesize}</span>
                        </td>
                    </tr>

                {/foreach}
            </table>

        </div>

    </div>	<!-- row -->
</div>	<!-- container -->



{include file="$themeDefaultPath/widget/footer.tpl" themePath=$themeDefaultPath}