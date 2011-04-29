{include file="findInclude:common/templates/header.tpl" customHeader=''}

<script type="text/javascript">
  var nativeAppLoginSuccess = true;
</script>

<div class="nonfocal">
  {include file="findInclude:modules/{$moduleID}/templates/banner.tpl"}
</div>

<div class="{if $tabletDisplay}loginBlock{else}nonfocal{/if}">
  <h1 style="text-align:center;height:300px;line-height:160px;">Loading...</h1>
</div>

{include file="findInclude:common/templates/footer.tpl"}
