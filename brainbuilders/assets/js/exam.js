// Exam Timer
function startTimer(duration, display) {
    let timer = duration, minutes, seconds;
    const interval = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = "Time Left: " + minutes + ":" + seconds;

        if (--timer < 0) {
            clearInterval(interval);
            alert("Time's up! Submitting your exam...");
            document.getElementById('examForm').submit();
        }
    }, 1000);
}

// Question Navigation
let currentQuestion = 0;
const questions = document.querySelectorAll('.question-box');

function showQuestion(index) {
    questions.forEach(q => q.style.display = 'none');
    questions[index].style.display = 'block';
    currentQuestion = index;
    
    // Update nav buttons
    document.querySelectorAll('.nav-btn').forEach((btn, i) => {
        btn.classList.toggle('active', i === index);
    });
    
    // Show/hide prev/next buttons
    document.getElementById('prevBtn').style.display = 
        (index === 0) ? 'none' : 'inline-block';
    document.getElementById('nextBtn').style.display = 
        (index === questions.length - 1) ? 'none' : 'inline-block';
}

function nextQuestion() {
    if (currentQuestion < questions.length - 1) {
        showQuestion(currentQuestion + 1);
    }
}

function prevQuestion() {
    if (currentQuestion > 0) {
        showQuestion(currentQuestion - 1);
    }
}

// Initialize
window.onload = function () {
    // Start timer
    const timerDisplay = document.getElementById('examTimer');
    const duration = parseInt(timerDisplay.dataset.duration, 10);
    startTimer(duration, timerDisplay);
    
    // Show first question
    showQuestion(0);
    
    // Prevent accidental navigation
    window.addEventListener('beforeunload', function (e) {
        e.preventDefault();
        e.returnValue = 'Are you sure you want to leave? Your progress may be lost.';
    });
};