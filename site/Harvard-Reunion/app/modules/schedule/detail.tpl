{include file="findInclude:common/header.tpl"}

{$firstSection = array_shift($sections)}
{$titleField = array_shift($firstSection)}

<div class="nonfocal">
  <a id="bookmark" class="{if $bookmarked}on{/if}" onclick="toggleBookmark('{$cookieName}', '{$eventId}', COOKIE_DURATION, COOKIE_PATH)"></a>
  <h2>{include file="findInclude:common/listItem.tpl" item=$titleField}</h2>
  {foreach $firstSection as $field}
    <p{if $field['class']} class="{$field['class']}"{/if}>
      {include file="findInclude:common/listItem.tpl" item=$field}
    </p>
  {/foreach}
</div>
  
{if count($sections)}
  {foreach $sections as $fields}
    {include file="findInclude:common/navlist.tpl" navlistItems=$fields accessKey=false subTitleNewline=true}
  {/foreach}
{/if}

{include file="findInclude:common/footer.tpl"}
