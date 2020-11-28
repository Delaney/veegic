import React from 'react';
import { Link } from 'react-router-dom';

function Dashboard() {
    return (
        <div>
			<div className="">
				<h1>Dashboard</h1>
			</div>

			<div className="grid grid-cols-2 gap-4">
				<div className="col">
					<span className="text-lg">Add Subtitles</span>
				</div>
				<div className="col">
					<span className="text-lg">Trim</span>
				</div>
			</div>
		</div>
    );
}

export default Dashboard;
