import React from 'react';
import ReactDOM from 'react-dom/client';
import QuizComponent from './quizComponent.js';  // Import your component

// Get the quiz data from a global variable set in the HTML
const quizData = window.quizData;


// Ensure React renders the component to the 'quiz-root' div
const rootElement = document.getElementById('quiz-root');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(<QuizComponent quizData={quizData} />);
} else {
    console.error("No element with id 'quiz-root' found.");
}
