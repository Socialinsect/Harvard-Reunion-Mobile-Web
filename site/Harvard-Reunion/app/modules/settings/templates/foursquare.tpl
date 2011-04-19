{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <p>The foursquare account {$username} is no longer authorized to use Harvard/Radcliffe College Reunions.  You will need to visit the foursquare mobile website to log out or switch users.  </p>
</div>

{$links = array()}

{$link = array()}
{$link['title'] = 'Go to foursquare.com'}
{$link['url'] = $foursquareURL}
{$link['class'] = 'external'}
{$link['linkTarget'] = 'reunionFoursquare'}
{$links[] = $link}

{$link = array()}
{$link['title'] = 'Return to Settings'}
{$link['url'] = $returnURL}
{$links[] = $link}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links}

{include file="findInclude:common/templates/footer.tpl"}
