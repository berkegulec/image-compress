import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';

export default function ImageCompressor() {
    const { flash = {} } = usePage().props;
    const [images, setImages] = useState([]);
    const [quality, setQuality] = useState(80);
    const [loading, setLoading] = useState(false);
    const [downloadUrl, setDownloadUrl] = useState(null);

    const handleImageChange = (e) => {
        const files = Array.from(e.target.files);
        const newImages = files.map(file => ({
            file,
            preview: URL.createObjectURL(file),
            originalSize: file.size,
            compressedSize: 0,
            id: Math.random().toString(36).substr(2, 9)
        }));
        setImages(prev => [...prev, ...newImages]);
        setDownloadUrl(null);
    };

    const handleCompress = () => {
        if (images.length === 0) return;

        const formData = new FormData();
        images.forEach((img, index) => {
            formData.append(`images[]`, img.file);
        });
        formData.append('quality', quality);
        setLoading(true);

        router.post('/compress', formData, {
            onSuccess: (page) => {
                const { flash = {} } = page.props;
                if (flash.compressedSizes && flash.zipUrl) {
                    setImages(prev => prev.map((img, index) => ({
                        ...img,
                        compressedSize: flash.compressedSizes[index]
                    })));
                    setDownloadUrl(flash.zipUrl);
                }
                setLoading(false);
            },
            onError: () => {
                setLoading(false);
            },
        });
    };

    const handleRemoveImage = (id) => {
        setImages(prev => prev.filter(img => img.id !== id));
        setDownloadUrl(null);
    };

    const handleClearAll = () => {
        setImages([]);
        setDownloadUrl(null);
    };

    useEffect(() => {
        return () => {
            // Cleanup object URLs when component unmounts
            images.forEach(img => URL.revokeObjectURL(img.preview));
        };
    }, []);

    const formatSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <>
            <Head title="Resim Sıkıştırma" />
            <div className="max-w-2xl mx-auto p-6">
                <div className="bg-white rounded-lg shadow-lg p-6">
                    <h1 className="text-2xl font-bold mb-6">Resim Sıkıştırma</h1>
                    
                    <div className="flex gap-4 mb-6">
                        <input
                            type="file"
                            accept="image/*"
                            onChange={handleImageChange}
                            className="flex-1 p-2 border rounded"
                            multiple
                        />
                        {images.length > 0 && (
                            <button
                                onClick={handleClearAll}
                                className="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                            >
                                Tümünü Temizle
                            </button>
                        )}
                    </div>

                    <div className="mb-6">
                        <label className="block mb-2">Kalite: {quality}%</label>
                        <input
                            type="range"
                            min="1"
                            max="100"
                            value={quality}
                            onChange={(e) => setQuality(e.target.value)}
                            className="w-full"
                        />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        {images.map((img) => (
                            <div key={img.id} className="relative border rounded p-4">
                                <button
                                    onClick={() => handleRemoveImage(img.id)}
                                    className="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center"
                                >
                                    ×
                                </button>
                                <img src={img.preview} alt="Preview" className="w-full h-48 object-cover rounded mb-2" />
                                <div className="text-sm">
                                    <p>Orijinal: {formatSize(img.originalSize)}</p>
                                    {img.compressedSize > 0 && (
                                        <p>Sıkıştırılmış: {formatSize(img.compressedSize)}</p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    {downloadUrl && (
                        <div className="mb-4">
                            <a
                                href={downloadUrl}
                                className="block w-full bg-green-500 text-white py-2 px-4 rounded text-center hover:bg-green-600 mb-4"
                                download
                            >
                                Sıkıştırılmış Resimleri İndir (ZIP)
                            </a>
                        </div>
                    )}

                    <button
                        onClick={handleCompress}
                        disabled={images.length === 0 || loading}
                        className={`w-full bg-blue-500 text-white py-2 px-4 rounded ${
                            (images.length === 0 || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'
                        }`}
                    >
                        {loading ? 'Sıkıştırılıyor...' : 'Resimleri Sıkıştır'}
                    </button>
                </div>
            </div>
        </>
    );
}
