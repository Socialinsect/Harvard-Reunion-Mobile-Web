{include file="findInclude:common/templates/header.tpl" isModuleHome=true}

<div class="nonfocal">
  {block name="twitterHeader"}
    <a class="tweetButton" href="{$tweetURL}"><span class="tweetLink">tweet</span></a>
    <h2>{$hashtag}</h2>
  {/block}
</div>

{foreach $posts as $i => $post}
  {$lastPost = $post@last}
  {capture name="title" assign="title"}
    {block name="tweetContent"}
      &ldquo;{$post['message']}&rdquo; 
      <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
    {/block}
  {/capture}
  {$posts[$i]['title'] = $title}
{/foreach}

{if !count($posts)}
  {$empty = array()}
  {$empty['title'] = 'No tweets for '|cat:$hashtag}
  {$posts[] = $empty}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$posts navlistID="listContainer"}

{block name="twitterFooter"}
  <div class="nonfocal">
    <span class="smallprint">View tweets for {$hashtag} at <a href="{$twitterURL}">twitter.com</a></span>
  </div>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
