{include file="findInclude:common/templates/header.tpl" isModuleHome=true}

<div class="nonfocal">
  <a class="tweetButton" href="{$tweetURL}" target="reunionTweet"><span class="tweetLink">tweet</span></a>
  <h2>{$hashtag}</h2>
</div>

{foreach $posts as $i => $post}
  {capture name="title" assign="title"}
    &ldquo;{$post['message']}&rdquo; 
    <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
  {/capture}
  {$posts[$i]['title'] = $title}
{/foreach}

{$more = array()}
{$more['title'] = '<span id="listFooter" class="tweetLink">More results at twitter.com</span>'}
{$more['class'] = 'external'}
{$more['url'] = $twitterURL}
{$posts[] = $more}

{include file="findInclude:common/templates/results.tpl" results=$posts resultslistID="listContainer"}

{include file="findInclude:common/templates/footer.tpl"}
