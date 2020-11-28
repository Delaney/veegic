import React from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route, Link } from 'react-router-dom';

import Home from './Home';
import Dashboard from './Dashboard';

function App() {
    return (
        <div>
            <Router>
                <Route exact path="/" component={ Home } />
                <Route path="/dashboard" component={ Dashboard } />
            </Router>
        </div>
    );
}

export default App;

if (document.getElementById('app')) {
    ReactDOM.render(<App />, document.getElementById('app'));
}
