{if count($post['comments'])}
  <div class="focal">
    {foreach $post['comments'] as $i => $comment}
      {$lastComment = $comment@last}
      {block name="commentContent"}
        <div class="comment">
          <img class="profilepic" src="{$comment['author']['photo']}" />
          <div class="wrapper">
            &ldquo;{$comment['message']|escape}&rdquo; 
            <span class="smallprint"> -&nbsp;{$comment['author']['name']|escape}, {$comment['when']['shortDelta']}</span>
          </div>
        </div>
      {/block}
    {/foreach}
  </div>
{/if}
