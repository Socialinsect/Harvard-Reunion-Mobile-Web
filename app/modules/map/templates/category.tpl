{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>Browse {$title}:</h2>
</div>

{include file="findInclude:common/templates/results.tpl" results=$places}

{if $categories|@count>1}
<div class="nonfocal">
  <form action="category.php" method="get">
    <select name="category" onchange="this.parentNode.submit();">
        <option value="" selected="selected">Browse map by:</option>
      {foreach $categories as $category}
        <option value="{$category['id']}">{$category['title']}</option>
      {/foreach}
    </select>
  </form>
</div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
