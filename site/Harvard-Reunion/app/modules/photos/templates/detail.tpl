{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal" id="navbar2">
  comment - like - etc
</div>

<div class="photo">
  <img src="{$photo['img']['src']}" />
</div>

<div class="nonfocal">
  <div class="message">{$photo['message']}</div>
  <div class="smallprint">
    Uploaded {$photo['when']['delta']} by 
    <a class="author" href="{$photo['author']['url']}">{$photo['author']['name']}</a>
  </div>
</div>

{if count($photo['comments'])}
  {foreach $photo['comments'] as $i => $comment}
    {capture name="title" assign="title"}
      "{$comment['message']}" 
      <span class="smallprint">
        <a class="author" href="{$comment['author']['url']}">
          {$comment['author']['name']}
        </a>, {$comment['when']['delta']}
      </span>
    {/capture}
    {$photo['comments'][$i]['title'] = $title}
  {/foreach}

  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$photo['comments']}
{/if}

{include file="findInclude:common/templates/footer.tpl"}