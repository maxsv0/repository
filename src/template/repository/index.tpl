{include file="$themeDefaultPath/widget/header.tpl" themePath=$themeDefaultPath}


<div class="container">
    <div class="row">

        <div class="col-md-12">

            <!-- page document -->
            {if $document}
                <h1>{$document.name}</h1>
                {$document.text}
            {/if}


            <table class="table">

                <tr>
                    <th>Name</th>
                    <th>Title</th>
                    <th>Release date</th>
                    <th>Description</th>
                    <th>Source</td>
                    <th>Files</th>
                </tr>


                {foreach from=$repository_list item=module}

                    <tr>
                        <td>{$module.title}</td>
                        <td>{$module.version}</td>
                        <td>{$module.date|substr:0:11}</td>
                        <td>{$module.description}</td>
                        <td>{$module.source}</td>
                        <td>
                            {$module.file}
                            <span class="text-muted">{$module.size|filesize}</span>
                        </td>
                    </tr>



                {/foreach}
            </table>

        </div>

    </div>	<!-- row -->
</div>	<!-- container -->



{include file="$themeDefaultPath/widget/footer.tpl" themePath=$themeDefaultPath}