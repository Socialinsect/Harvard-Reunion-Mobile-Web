{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" placeholder="Search Map" emphasized=false}

<div class="nonfocal searchHeader">Events</div>
{include file="findInclude:common/templates/results.tpl" results=$eventPlaces}

<div class="nonfocal searchHeader">Places</div>
{include file="findInclude:common/templates/results.tpl" results=$places}

{include file="findInclude:common/templates/footer.tpl"}
