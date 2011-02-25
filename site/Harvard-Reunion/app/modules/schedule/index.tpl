{include file="findInclude:common/header.tpl"}

<div class="nonfocal" id="navbar2">
  <form method="get" action="./">
    <select name="day" onchange="loadDays(this);">
      <option value="all">All Events</option>
      {foreach $days as $value => $title}
        {if $day == $value}
          <option value="{$value}" selected="true">{$title}</option>
        {else}
          <option value="{$value}">{$title}</option>
        {/if}
      {/foreach}
    </select>

    
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
</div>

{foreach $eventDays as $date => $dayInfo}
  <div class="nonfocal dateHeader">
    {$dayInfo['title']}
  </div>
  {include file="findInclude:common/results.tpl" results=$dayInfo['events']}
{/foreach}

{include file="findInclude:common/footer.tpl"}
