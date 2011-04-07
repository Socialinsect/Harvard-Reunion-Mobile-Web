{extends file="findExtends:modules/$moduleID/templates/harris.tpl"}

{block name="inputs"}
  <table class="inputs">
    <tr>
      <td><label for="username">User ID:</label></td>
      <td><input type="text" id="username" name="loginUser" /></td>
    </tr>
    <tr>
      <td><label for="pwd">Password:</label></td>
      <td><input type="password" id="pwd" name="loginPassword" /></td>
    </tr>
  </table>
  <br/>
{/block}
