import React from 'react';
import { Link } from 'react-router-dom';

function Home() {
    return (
        <div className="row justify-content-center">
			<div className="col-md-8">
				<div className="card">
					<div className="">
						<h1>Do Stuff With Your Videos</h1>
					</div>

					<div className="card-body">
						<Link to="/dashboard" title="Enter">Enter</Link>
					</div>
				</div>
			</div>
		</div>
    );
}

export default Home;
