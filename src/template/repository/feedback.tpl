{include file="$themeDefaultPath/widget/header.tpl" themePath=$themeDefaultPath}

<div class="container top-menu">
    <div class="row">
        {include file="$themeDefaultPath/widget/menu-top.tpl"}
    </div>
</div>

{include file="$themeDefaultPath/widget/navigation.tpl"}

<div class="container">
    <div class="row content-block">

        {if $document.name}
            <div class="col-sm-12"><h1>{$document.name}</h1></div>
        {/if}
        <div class="col-sm-5">
            {$document.text}
        </div>
        <div class="col-sm-7">
            {include file="$themeDefaultPath/widget/messages.tpl"}

            {include file="$themePath/feedback/main.tpl"}
        </div>
    </div>
</div>


{include file="$themeDefaultPath/widget/footer.tpl" themePath=$themeDefaultPath}