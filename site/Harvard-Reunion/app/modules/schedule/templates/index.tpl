{include file="findInclude:common/templates/header.tpl"}

<div id="navbar2">
  <form method="get" action="./">
    <select name="category" onchange="loadCategory(this);">
      {foreach $categories as $value => $title}
        {if $category == $value}
          <option value="{$value}" selected="true">{$title}</option>
        {else}
          <option value="{$value}">{$title}</option>
        {/if}
      {/foreach}
    </select>
    {block name="submit"}
    {/block}
    
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
</div>

{if count($eventDays)}
  {foreach $eventDays as $date => $dayInfo}
    <div class="nonfocal dateHeader">
      {$dayInfo['title']}
    </div>
    {include file="findInclude:common/templates/results.tpl" results=$dayInfo['events']}
  {/foreach}
{else}
  <div class="nonfocal">
    {if $category == 'mine'}
      There are no events in my schedule
    {else}
      There are no {$categories[$category]|lower} for this reunion
    {/if}
  </div>
{/if}
{include file="findInclude:common/templates/footer.tpl"}
