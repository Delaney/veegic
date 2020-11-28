import axios from "axios";



class UploadService {
	upload(video, onUploadProgress) {
		let formData = new FormData();

		formData.append("video", video);

		return axios.post('/upload', formData, {
			headers: {
				"Content-type": "multipart/form-data"
			},
			onUploadProgress
		});
	}

	getVideos() {
		return axios.get("/videos", {
			headers: {
				"Content-type": "application/json"
			},
		});
	}
}

export default new UploadService();