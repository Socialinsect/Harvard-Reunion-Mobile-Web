{foreach $posts as $i => $post}
  {$lastPost = $post@last}
  {capture name="title" assign="title"}
    {block name="tweetContent"}
      &ldquo;{$post['message']}&rdquo; 
      <span class="smallprint"> - {$post['author']['name']}, {$post['when']['shortDelta']}</span>
    {/block}
  {/capture}
  {$posts[$i]['title'] = $title}
{/foreach}

{if !count($posts)}
  {$empty = array()}
  {$empty['title'] = 'No tweets for '|cat:$hashtag}
  {$posts[] = $empty}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$posts}
