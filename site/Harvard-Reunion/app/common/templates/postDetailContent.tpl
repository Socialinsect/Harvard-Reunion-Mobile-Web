{foreach $post['comments'] as $i => $comment}
  {capture name="title" assign="title"}
    {block name="comment"}
      &ldquo;{$comment['message']}&rdquo; 
      <span class="smallprint"> -&nbsp;{$comment['author']['name']}, {$comment['when']['shortDelta']}</span>
    {/block}
  {/capture}
  {$post['comments'][$i]['title'] = $title}
{/foreach}

{if count($post['comments'])}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$post['comments'] accessKey=false}
{/if}
