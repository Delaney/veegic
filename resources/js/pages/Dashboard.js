import React from 'react';
import { Link } from 'react-router-dom';

import Upload from '../components/Upload';

function Dashboard() {
    return (
        <div>
			<div className="">
				<h1>Dashboard</h1>
			</div>

			<div className="mt-4">
				<Upload />
			</div>
		</div>
    );
}

export default Dashboard;
