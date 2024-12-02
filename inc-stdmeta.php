<meta charset="UTF-8">
<meta name="viewport" content="width-device-width, initial-scale=1.0">
<style>
  h1,h2,h3,p,a{
    font-family: sans-serif;
    font-weight: lighter;
  }
  h4{
    margin: 0em;
  }
  table{
    font-family: sans-serif;
    font-weight: lighter;
  }
  td{
    font-family: sans-serif;
    font-weight: lighter;
    padding-top: 0.75em;
    padding-bottom: 0.75em;
    padding-left: 0.25em;
    padding-right: 0.25em;
  }
  table,th,td{
    border: 2px solid;
    border-collapse: collapse;
    border-color: black;
    height: fit-content;
    width: fit-content;
  }
  th{
    padding-top: 0.5em;
    padding-bottom: 0.5em;
    padding-left: 0.25em;
    padding-right: 0.25em;
  }
  body{
    background-color:beige;
    margin: 0.75em;
  }
  .sub-element{
    border: 2px solid;
    border-color: black;
    padding: 0em;
  }
  .sub-table{
    margin: 0em;
    border: 0px;
    padding: 0em;
    height: 100%;
    width: 100%;
  }
  .sub-row{
    border: 0px;
    padding: 0em;
    text-align: center;
  }
  .sub-top{
    border-top: 0px;
    border-bottom: 2px solid;
    border-left: 0px;
    border-right: 0px;
    border-color: black;
    padding: 0em;
    text-align: center;
  }
  .sub-bottom{
    border-top: 2px ridge;
    border-bottom: 2px groove;
    border-left: 2px ridge;
    border-right: 2px groove;
    border-color: black;
    padding: 0em;
    text-align: center;
  }
  .sub-data{
    border: 0px;
    padding: 0.25em;
  }
  .button-link{
    border-top: 2px ridge;
    border-bottom: 2px groove;
    border-left: 2px ridge;
    border-right: 2px groove;
    border-color: black;
    padding: 0.25em;
    text-align: center;
  }
  .nested-td{
    border: 0px;
    padding: 0px;
  }
  .grayed{
    color: dimgray;
    border-color: black;
    background-color: rgba(0,0,0,0.1);
  }
  .field-field * {
    border: 0px solid;
    padding: 5px;
  }
  .radio-field * {
    border: 0px solid;
    padding: 5px;
  }
  .radio-label{
    font-weight: bold;
    font-size: 1em;
    text-align: right;
    padding: 0px;
  }
  .submit-button{
    border: 0.1875em solid;
    font-size: 1.125em;
    padding-top: 0.25em;
    padding-bottom: 0.25em;
    padding-left: 0.375em;
    padding-right: 0.375em;
    margin-left: 0.09375em;
  }
  .search{
    border: 0.125em solid;
    font-size: 1.0625em;
    margin-left: 0.34375em;
    width: 94%;
    background-color: #f4f4f8;
  }
  .search-filter{
    border: 0.125em solid;
    font-size: 1.0625em;
    margin-left: 0.5em;
  }
  /*created for changepassword.php*/
  .field-field * {
    border: 0px solid;
    padding: 5px;
  }
  .field-label{
    padding: 0px;
    font-weight: bold;
    font-size: 1em;
    text-align: right;
  }
  .textbox{
    margin-left: 0.25em;
    border: 0.125em solid;
    background-color: #f4f4f8;
    font-size: 1.0625em;
  }
  .change-button{
    margin-left: 0.25em;
    border: 0.1875em solid;
    padding-top: 0.25em;
    padding-bottom: 0.25em;
    padding-left: 0.375em;
    padding-right: 0.375em;
    font-size: 1.125em;
  }
  .error{
    margin: 0em;
    padding: 0em;
    color: #FF0000;
    font-weight: bold;
  }
</style>