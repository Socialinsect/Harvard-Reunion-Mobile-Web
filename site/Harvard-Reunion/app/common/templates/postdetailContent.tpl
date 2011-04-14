{if count($post['comments'])}
  <div class="focal">
    {foreach $post['comments'] as $i => $comment}
      {$lastComment = $comment@last}
      {block name="commentContent"}
        <div class="comment">
          &ldquo;{$comment['message']}&rdquo; 
          <span class="smallprint"> -&nbsp;{$comment['author']['name']}, {$comment['when']['shortDelta']}</span>
        </div>
      {/block}
    {/foreach}
  </div>
{/if}
