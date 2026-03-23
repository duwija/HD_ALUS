@extends('layout.main')

@section('title', 'MikroTik Burst Limit Calculator')

@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h1>MIKROTIK BURST LIMIT CALCULATOR</h1>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
      <div id="wrap">

        <div class="">
          <div class="row">
            <div class="card col-md-6 row m-1" >
              <div class="row">
                <!-- Upload Section -->
                <div class="col-md-6">
                  <div class="mb-3">
                    <label><b>UPLOAD</b></label><br>
                    <label>Max Limit (K/M)</label><br>
                    <input onkeydown="upperCaseF(this)" type="text" id="up-max-limit" class="form-control text-success" placeholder="7M" value="7M" onkeyup="burhCalc()" oninput="validateInput(this)">
                  </div>
                  <div class="mb-3">
                    <label>Burst Limit (K/M)</label><br>
                    <input onkeydown="upperCaseF(this)" type="text" id="up-speed-bonus" class="form-control text-primary" placeholder="10M" value="10M" onkeyup="burhCalc()" oninput="validateInput(this)">
                  </div>
                  <div class="mb-4">
                    <label>Burst Duration (second)</label><br>
                    <input onkeydown="upperCaseF(this)" type="text" id="up-interval-bonus" class="form-control text-danger" placeholder="10" value="10" onkeyup="burhCalc()" oninput="validateInput(this)">
                  </div>
                </div>

                <!-- Download Section -->
                <div class="col-md-6">
                  <div class="mb-3">
                    <label><b>DOWNLOAD</b></label><br>
                    <label>Max Limit (K/M)</label><br>
                    <input onkeydown="upperCaseF(this)" type="text" id="down-max-limit" class="form-control text-success" placeholder="7M" value="7M" onkeyup="burhCalc()" oninput="validateInput(this)">
                  </div>
                  <div class="mb-3">
                    <label>Burst Limit (K/M)</label><br>
                    <input onkeydown="upperCaseF(this)" type="text" id="down-speed-bonus" class="form-control text-primary" placeholder="10M" value="10M" onkeyup="burhCalc()" oninput="validateInput(this)">
                  </div>
                  <div class="mb-4">
                    <label>Burst Duration (second)</label><br>
                    <input onkeydown="upperCaseF(this)" type="text" id="down-interval-bonus" class="form-control text-danger" placeholder="10" value="10" onkeyup="burhCalc()">
                  </div>
                </div>
              </div>


            </div>
            <div class="m-1 col-md-5">
              <div class="col-md-12 card">
                <span style="margin-left:2px;font-size:15px"><b>COPY PASTE BURST RESULT TO MIKROTIK</b></span>
                <div class="" style="margin-bottom:10px">
                  <table class="table" style="font-weight:normal">
                    <thead>
                      <tr style="background-color: #eee;font-weight:normal">
                        <th colspan="2" style="background-color: #fff;">Tab General</th>
                        <th style="background-color: #ddd;" >Upload</th>
                        <th style="background-color: #ddd;">Download</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <th colspan="2" style="background-color: #eee; font-weight:normal">Max Limit</th>
                        <td id="result-up-max-limit" style='font-weight:bold; color: DarkGreen'>512K</td>
                        <td id="result-down-max-limit" style='font-weight:bold;color: DarkGreen'>1M</td>
                      </tr>
                      <tr>
                        <th colspan="2" style="background-color: #eee; font-weight:normal">Burst Limit</th>
                        <td id="result-up-burst-limit" style='font-weight:bold;color: #01A3DF'>1M</td>
                        <td id="result-down-burst-limit" style='font-weight:bold;color: #01A3DF'>2M</td>
                      </tr>
                      <tr>
                        <th colspan="2" style="background-color: #eee; font-weight:normal">Burst Threshold</th>
                        <td id="result-up-burst-threshold" style='font-weight:bold;color: #191970' >384K</td>
                        <td id="result-down-burst-threshold" style='font-weight:bold;color: #191970'>750K</td>
                      </tr>
                      <tr>
                        <th colspan="2" style="background-color: #eee; font-weight:normal">Burst Time</th>
                        <td id="result-up-burst-time" style='font-weight:bold;color: DarkViolet'>16</td>
                        <td id="result-down-burst-time" style='font-weight:bold;color: DarkViolet' >16</td>
                      </tr>
                      <tr>
                        <th colspan="2" style="background-color: #fff; font-weight:bold">Tab Advanced</th>
                        <td style="background-color: #ddd;font-weight:bold">Upload</td>
                        <td style="background-color: #ddd;font-weight:bold">Download</td>
                      </tr>
                      <tr>
                        <th colspan="2" style="background-color: #eee; font-weight:normal">Limit At</th>
                        <td id="result-up-limit-at" style='font-weight:bold;color: Chocolate' >64K</td>
                        <td id="result-down-limit-at" style='font-weight:bold;color: Chocolate'>125K</td>
                      </tr>
                      <tr>
                        <th colspan="2" style="background-color: #eee; font-weight:normal">Priority</th>
                        <td style='font-weight:bold;color: DarkCyan'>8</td>
                        <td style='font-weight:bold;color: DarkCyan'>8</td>
                      </tr>

                      <tr>
                        <th colspan="2" style="background-color: #c5dded; font-weight:bold;">Rate Limit for PPPOE Profile</th>
                        <td colspan="2"><span id="rate-up-max-limit" style='color: DarkGreen'>512K</span>/<span id="rate-down-max-limit" style='color: DarkGreen'>1M</span> <span id="rate-up-burst-limit"style='color: #01A3DF' >1M</span>/<span id="rate-down-burst-limit" style='color: #01A3DF'>2M</span> <span id="rate-up-burst-threshold" style='color: #191970'>384K</span>/<span id="rate-down-burst-threshold" style='color: #191970'>750K</span> <span id="rate-up-burst-time" style='color: DarkViolet'>16</span>/<span id="rate-down-burst-time" style='color: DarkViolet'>16</span> <span style="color:DarkCyan">8</span> <span id="rate-up-limit-at" style='color: Chocolate' >64K</span>/<span id="rate-down-limit-at" style='color: Chocolate'>125K</span></td>

                      </tr>
                    </tbody>
                  </table>
                  <!-- <br> -->
             <!--      <table id="mytable col-md-12 table" style="font-weight:normal">
                    <thead>
                      <tr style="background-color: #ddd; " >
                        <td><b>Rate Limit for <span style="color:red">Hotspot</span> and <span style="color:red">PPPoE</span><b></td>
                        </tr>
                      </thead>
                      <tr style="font-size:15px;font-weight:bold">
                        <td><span id="rate-up-max-limit" style='color: DarkGreen'>512K</span>/<span id="rate-down-max-limit" style='color: DarkGreen'>1M</span> <span id="rate-up-burst-limit"style='color: #01A3DF' >1M</span>/<span id="rate-down-burst-limit" style='color: #01A3DF'>2M</span> <span id="rate-up-burst-threshold" style='color: #191970'>384K</span>/<span id="rate-down-burst-threshold" style='color: #191970'>750K</span> <span id="rate-up-burst-time" style='color: DarkViolet'>16</span>/<span id="rate-down-burst-time" style='color: DarkViolet'>16</span> <span style="color:DarkCyan">8</span> <span id="rate-up-limit-at" style='color: Chocolate' >64K</span>/<span id="rate-down-limit-at" style='color: Chocolate'>125K</span></td></th>
                      </tr>
                    </table> -->
                  </div>
                </div>
              </div>
            </div>


          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('footer-scripts')

<script>
 document.addEventListener("DOMContentLoaded", function() {
  burhCalc();
});
 function upperCaseF(inputField) {
  setTimeout(function() {
    inputField.value = inputField.value.toUpperCase();
  }, 1);
}

function burhCalc() {
 var upMaxLimitValue = document.getElementById("up-max-limit").value;

// Check if the value ends with "M"
 if (upMaxLimitValue.endsWith("M")) {
    // Remove the "M", parse the number, and multiply by 100
  var upLimit = parseInt(upMaxLimitValue.slice(0, -1)) * 1000;
} else {
    // Just parse the value as it is
  var upLimit = parseInt(upMaxLimitValue);
}


var downMaxLimitValue = document.getElementById("down-max-limit").value;

// Check if the value ends with "M"
if (downMaxLimitValue.endsWith("M")) {
    // Remove the "M", parse the number, and multiply by 100
  var downLimit = parseInt(downMaxLimitValue.slice(0, -1)) * 1000;
} else {
    // Just parse the value as it is
  var downLimit = parseInt(downMaxLimitValue);
}

  // var downMaxLimit = document.getElementById('down-max-limit').value;
  // var downMaxLimitLetter = downMaxLimit.match(/[a-zA-Z]/);
  // var downMaxLimitValue = downMaxLimit.replace(/[^0-9]/g, '');



var upSpeedBonusValue = document.getElementById("up-speed-bonus").value;

// Check if the value ends with "M"
if (upSpeedBonusValue.endsWith("M")) {
    // Remove the "M", parse the number, and multiply by 100
  var upSpeedBonus = parseInt(upSpeedBonusValue.slice(0, -1)) * 1000;
} else {
    // Just parse the value as it is
  var upSpeedBonus = parseInt(upSpeedBonusValue);
}



var downSpeedBonusValue = document.getElementById("down-speed-bonus").value;

// Check if the value ends with "M"
if (downSpeedBonusValue.endsWith("M")) {
    // Remove the "M", parse the number, and multiply by 100
  var downSpeedBonus = parseInt(downSpeedBonusValue.slice(0, -1)) * 1000;
} else {
    // Just parse the value as it is
  var downSpeedBonus = parseInt(downSpeedBonusValue);
}


  // var upSpeedBonus = document.getElementById('up-speed-bonus').value;
  // var upSpeedBonusLetter = upSpeedBonus.match(/[a-zA-Z]/);
  // var upSpeedBonusValue = upSpeedBonus.replace(/[^0-9]/g, '');

  // var downSpeedBonus = document.getElementById('down-speed-bonus').value;
  // var downSpeedBonusLetter = downSpeedBonus.match(/[a-zA-Z]/);
  // var downSpeedBonusValue = downSpeedBonus.replace(/[^0-9]/g, '');

var upIntervalBonus = parseInt(document.getElementById('up-interval-bonus').value);
var downIntervalBonus = parseInt(document.getElementById('down-interval-bonus').value);

    // Parse values based on 'M' for Mbps conversion
      // var upLimit = upMaxLimitLetter === 'M' ? parseInt(parseFloat(upMaxLimitValue) * 1000) : parseInt(upMaxLimitValue);

  //var downLimit = downMaxLimitLetter === 'M' ? parseInt(downMaxLimitValue * 1000) : parseInt(downMaxLimitValue);
 // var upSpeedBonus = upSpeedBonusLetter === 'M' ? parseInt(upSpeedBonusValue * 1000) : parseInt(upSpeedBonusValue);
  //var downSpeedBonus = downSpeedBonusLetter === 'M' ? parseInt(downSpeedBonusValue * 1000) : parseInt(downSpeedBonusValue);
  // console.log('upLimit:', upLimit);
    // Calculations
var upBurstTime = Math.ceil(upIntervalBonus * upSpeedBonus / (upLimit * (3 / 4)));
var downBurstTime = Math.ceil(downIntervalBonus * downSpeedBonus / (downLimit * (3 / 4)));
var upBurstThreshold = Math.ceil(upLimit * (3 / 4));
var downBurstThreshold = Math.ceil(downLimit * (3 / 4));
var upLimitAt = Math.ceil(upLimit * (1 / 8));
var downLimitAt = Math.ceil(downLimit * (1 / 8));
var rateUpBurst = Math.ceil(upBurstThreshold * (upIntervalBonus * upSpeedBonus / upBurstThreshold) / upSpeedBonus);
var rateDownBurst = Math.ceil(downBurstThreshold * (downIntervalBonus * downSpeedBonus / downBurstThreshold) / downSpeedBonus);

    // Output results
document.getElementById('result-up-max-limit').innerHTML = upMaxLimitValue;
document.getElementById('result-down-max-limit').innerHTML = downMaxLimitValue;
document.getElementById('rate-up-burst-limit').innerHTML = upSpeedBonusValue;
document.getElementById('result-down-burst-limit').innerHTML = downSpeedBonusValue;
document.getElementById('result-up-burst-limit').innerHTML = upSpeedBonusValue;
document.getElementById('rate-down-burst-limit').innerHTML = downSpeedBonusValue;
document.getElementById('result-up-burst-threshold').innerHTML = upBurstThreshold + 'K';
document.getElementById('result-down-burst-threshold').innerHTML = downBurstThreshold + 'K';
document.getElementById('result-up-burst-time').innerHTML = upBurstTime;
document.getElementById('result-down-burst-time').innerHTML = downBurstTime;
document.getElementById('result-up-limit-at').innerHTML = upLimitAt + 'K';
document.getElementById('result-down-limit-at').innerHTML = downLimitAt + 'K';
document.getElementById('rate-up-limit-at').innerHTML = upLimitAt + 'K';
document.getElementById('rate-down-limit-at').innerHTML = downLimitAt + 'K';

    // Additional calculations
document.getElementById('rate-up-max-limit').innerHTML = upMaxLimitValue;
document.getElementById('rate-down-max-limit').innerHTML = downMaxLimitValue;
document.getElementById('rate-up-burst-threshold').innerHTML = upBurstThreshold + 'K';
document.getElementById('rate-down-burst-threshold').innerHTML = downBurstThreshold + 'K';
document.getElementById('rate-up-burst-time').innerHTML = upBurstTime;
document.getElementById('rate-down-burst-time').innerHTML = downBurstTime;

    // Handle cases for zero values
if (upSpeedBonus === 0) {
  document.getElementById('rate-up-burst-limit').innerHTML = '0';
  document.getElementById('result-up-burst-threshold').innerHTML = '0';
}
if (downSpeedBonus === 0) {
  document.getElementById('rate-down-burst-limit').innerHTML = '0';
  document.getElementById('result-down-burst-threshold').innerHTML = '0';
}
}

</script>
<script>
  function validateInput(input) {
    // Allow only numbers, 'm', 'M', 'k', and 'K'
    input.value = input.value.replace(/[^0-9mMkK]/g, '');
  }
  function validateNumber(input) {
    // Allow only numbers, 'm', 'M', 'k', and 'K'
    input.value = input.value.replace(/[^0-9]/g, '');
  }
</script>
@endsection