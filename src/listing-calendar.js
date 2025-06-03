import ReactDOM from 'react-dom/client';
import App from './components/ListingCalendar';

// Container
const container = document.getElementById('evenimentul-listing-calendar-app');

// Root
const root = ReactDOM.createRoot(container);

// Render
root.render(<App/>);