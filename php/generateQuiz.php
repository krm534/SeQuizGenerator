<!doctype html>
<html lang="en">
<meta charset="UTF-8"> 
<head>
  <title> Take a Quiz </title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous"> 
</head>
<body onload="checkform()">

<?php
//error_reporting(E_ALL); ini_set('display_errors', '1');
echo 
'
	<div class="container">
  	<h2>Quiz In Progress</h2>
  	<p>Select an answer for each question. Press the submit button when finished.</p>
';

//there was an error with unicode and apostrophes so this should fix it
function fixString($string) {
    return preg_replace('/[^\x00-\x7f]/', '\'', $string);
}

//function that prints out the the quiz questions
function printQuestion($row){
    //for each answer, the pair of [answer, questionID] is passed into the post array
    echo fixString($row["question"]) . "<br>";
    echo '<input type="radio" name="question'. $row['quesID']. '" value="[a, '.$row['quesID'].']" required>A = ' . fixString($row["A"]) . '<br>';
    echo '<input type="radio" name="question'. $row['quesID']. '" value="[b, '.$row['quesID'].']">B = ' . fixString($row["B"]) . '<br>';
    if($row["C"] != null){
        echo '<input type="radio" name="question'. $row['quesID']. '" value="[c, '.$row['quesID'].']">C = ' . fixString($row["C"]) . '<br>';
    }
    if($row["D"] != null){
        echo '<input type="radio" name="question'. $row['quesID']. '" value="[d, '.$row['quesID'].']">D = ' . fixString($row["D"]) . '<br>';
    }
    if($row["E"] != null){
        echo '<input type="radio" name="question'. $row['quesID']. '" value="[e, '.$row['quesID'].']">E = ' . fixString($row["E"]) . '<br>';
    }
    echo '<br>';      
}

require 'loginInfo.php';
// Create connection
$conn = new mysqli($servername, $username, $password, $database);
/*
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully<br>";
*/
echo '<form action="results.php" method="post">';

//this checks if the input is from the index.html page
if(isset($_POST['questions'])){
    $questions=$_POST['questions'];
    $chapterQuestionPair = array(); 
//create pairs of chapters and amount of questions
    for($it = 0; $it < 10; $it++){
        if($questions[$it] > 0){
            array_push($chapterQuestionPair, array(($it+1), $questions[$it]));
        }
    }
    $questionIdArray = array();
    //Make each query and send it off to find the results
    for($i = 0; $i < count($chapterQuestionPair); $i++){
        $questionAnswerQuery = "select Questions.questionID as quesID, chapter, question, Answer1.answer as A,Answer2.answer as B,Answer3.answer as C, Answer4.answer as D, Answer5.answer as E from Questions, Answer1, Answer2,Answer3,Answer4,Answer5 where Questions.questionID = Answer1.questionID and Questions.questionID = Answer2.questionID and Questions.questionID = Answer3.questionID and Questions.questionID = Answer4.questionID and Questions.questionID = Answer5.questionID";
        $chapter = $chapterQuestionPair[$i][0];
        $numberOfQuestions = $chapterQuestionPair[$i][1]; 
        $questionAnswerQuery = $questionAnswerQuery . " and Questions.chapter = " . $chapter;
        $results = $conn->query($questionAnswerQuery);

        //prepare the results of the query to be printed out
        if($results->num_rows > 0){
            $randomArray = array(); 
            //output the data found
            while($row = $results->fetch_assoc()){
                array_push($randomArray, $row);
            }
            //randomize the array of questions from the chapter
            shuffle($randomArray);
            //print out the number of quesitons
            for($x = 0; $x < $numberOfQuestions; $x++){
                printQuestion($randomArray[$x]);
                //push the questionIDs to the questionIdArray
                array_push($questionIdArray, $randomArray[$x]['quesID']);
            }
        }
        else{
            echo 'Result is 0';
        }
    }
    //sort the questionIdArray
    sort($questionIdArray);
    //make the questionIdArray into a string
    $questionIdString = implode(', ', $questionIdArray);
    //makes the chapter array into a string
    $chapterString = implode(',', $questions); 
    //checks if the exact quiz already exists in the saved quizzes database
    $checkSavedQuizzes = "select * from SavedQuizzes where questions = '" . $questionIdString . "' and chapters = '" . $chapterString . "'";
    $checkSaved = $conn->query($checkSavedQuizzes);
    //if it does not exist, put the quiz in the database
    if($checkSaved->num_rows == 0){
        $savedQuizzesQuery = "insert into SavedQuizzes(questions, chapters) values ('". $questionIdString ."', '" . $chapterString . "')";
        $results = $conn->query($savedQuizzesQuery);
    }
}


//this checks if the input came from SavedQuizzes.php
if(isset($_POST['savedQuestions'])){
    $savedQuestions = explode(', ', $_POST['savedQuestions']);
    $questionAnswerQuery = "select Questions.questionID as quesID, chapter, question, Answer1.answer as A,Answer2.answer as B,Answer3.answer as C, Answer4.answer as D, Answer5.answer as E from Questions, Answer1, Answer2,Answer3,Answer4,Answer5 where Questions.questionID = Answer1.questionID and Questions.questionID = Answer2.questionID and Questions.questionID = Answer3.questionID and Questions.questionID = Answer4.questionID and Questions.questionID = Answer5.questionID and (";
    //loop through the array adding the questionIDs to the query
    for($x = 0; $x < count($savedQuestions); $x++){
        //if it is the last element of the array end the query
        if($x == (count($savedQuestions)) - 1){
            $questionAnswerQuery = $questionAnswerQuery . " Questions.questionID = " . $savedQuestions[$x] . ")"; 
        }
        else{
            $questionAnswerQuery = $questionAnswerQuery . " Questions.questionID = " . $savedQuestions[$x] . " or";
        }
    }
    //echo $questionAnswerQuery;
    $results = $conn->query($questionAnswerQuery);
    if($results->num_rows > 0){
        while($row = $results->fetch_assoc()){
            printQuestion($row);
        }
    }

}

//selects every question and answer
//select question, Answer1.answer as A,Answer2.answer as B,Answer3.answer as C, Answer4.answer as D, Answer5.answer as E from Questions, Answer1, Answer2,Answer3,Answer4,Answer5 where Questions.questionID = Answer1.questionID and Questions.questionID = Answer2.questionID and Questions.questionID = Answer3.questionID and Questions.questionID = Answer4.questionID and Questions.questionID = Answer5.questionID;

echo 
"
	<button type=\"submit\" class=\"btn btn-primary\" id=\"submit\">Submit</button>
";

echo '</form>';
echo '</div>';

?>

</body>
</html>
