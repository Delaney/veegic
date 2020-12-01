import React, { useState, useEffect } from 'react';
import UploadService from '../services/upload.service';

function Upload(props) {

	const [selectedVideo, setVideo] = useState(null);
	const [currentVideo, setCurrentVideo] = useState(null);
	const [progress, setProgress] = useState(0);
	const [message, setMessage] = useState("");
	const [videoInfos, setVideoInfos] = useState([]);

	const selectVideo = (event) => {
		console.log(event.target.files[0]);
		setVideo(event.target.files[0]);
		setProgress(69);
		console.log(progress);
	}

	const upload = () => {
		let currentFile = selectedVideo;

		setCurrentVideo(currentFile);

		UploadService.upload(currentFile, (event) => {
			setProgress(Math.round((100 * event.loaded) / event.total));
		}).then((response) => {
			setMessage(response.data.message);
			return UploadService.getVideos();
		}).then((videos) => {
			setVideoInfos(videos.data)
		}).catch(() => {
			setProgress(0);
			setMessage("Could not upload this video!");
			// setCurrentVideo(null);
		});

		setVideo(null);
	}

	useEffect(() => {
		// UploadService.getVideos()
		// 	.then((response) => {
		// 		setVideoInfos(response.data);
		// 	});
	}, []);

	return (
		<div>
			{currentVideo && (
				<div className="shadow w-full bg-grey-light mt-2">
					<div
						className="bg-teal text-xs leading-none py-1 text-center text-white"
						role="progressbar"
						aria-valuenow={progress}
						aria-valuemin="0"
						aria-valuemax="100"
						style={{ width: progress + "%" }}>
							{progress}%
						</div>
				</div>
			)}

			<label className="btn btn-default">
				<input type="file" onChange={ selectVideo } />
			</label>

			<button
				className="bg-black text-white py-2 px-3"
				// disabled={!selectedVideo}
				onClick={ upload }
			>
				Upload
			</button>

			<div className="alert alert-light" role="alert">
				{message}
			</div>

			<div className="card">
				<div>List of Videos</div>

				<ul>
					{videoInfos &&
						videoInfos.map((video, index) => (
							<li className="list-group-item" key={index}>
								<a href={file.url}>{file.name}</a>
							</li>
						))
					}
				</ul>
			</div>
		</div>
	)
}

export default Upload;