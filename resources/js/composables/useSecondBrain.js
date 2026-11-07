import { ref } from 'vue';
import api from '../services/api';

const collections = ref([]);
const currentCollection = ref(null);
const currentNote = ref(null);
const unassignedArticles = ref([]);
const isLoading = ref(false);
const isSaving = ref(false);

export function useSecondBrain() {
    const fetchCollections = async () => {
        isLoading.value = true;
        try {
            const res = await api.get('/user/collections');
            collections.value = res.data.data;
            return res.data;
        } finally {
            isLoading.value = false;
        }
    };

    const fetchCollection = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.get(`/user/collections/${id}`);
            currentCollection.value = res.data.data;
            return currentCollection.value;
        } finally {
            isLoading.value = false;
        }
    };

    const createCollection = async (data) => {
        isSaving.value = true;
        try {
            const res = await api.post('/user/collections', data);
            await fetchCollections();
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const updateCollection = async (id, data) => {
        isSaving.value = true;
        try {
            const res = await api.put(`/user/collections/${id}`, data);
            if (currentCollection.value && currentCollection.value.id === id) {
                currentCollection.value = { ...currentCollection.value, ...res.data.data };
            }
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const deleteCollection = async (id) => {
        try {
            const res = await api.delete(`/user/collections/${id}`);
            collections.value = collections.value.filter((c) => c.id !== id);
            return res.data;
        } catch (e) {
            throw e;
        }
    };

    // Section Operations
    const createSection = async (collectionId, data) => {
        isSaving.value = true;
        try {
            const res = await api.post(`/user/collections/${collectionId}/sections`, data);
            await fetchCollection(collectionId);
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const deleteSection = async (collectionId, sectionId) => {
        try {
            const res = await api.delete(`/user/sections/${sectionId}`);
            await fetchCollection(collectionId);
            return res.data;
        } catch (e) {
            throw e;
        }
    };

    // Note Operations
    const createNote = async (collectionId, sectionId, data) => {
        isSaving.value = true;
        try {
            const res = await api.post(`/user/sections/${sectionId}/notes`, data);
            await fetchCollection(collectionId);
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const fetchNote = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.get(`/user/notes/${id}`);
            currentNote.value = res.data.data;
            return currentNote.value;
        } finally {
            isLoading.value = false;
        }
    };

    const updateNote = async (id, data) => {
        isSaving.value = true;
        try {
            const res = await api.put(`/user/notes/${id}`, data);
            if (currentNote.value && currentNote.value.id === id) {
                currentNote.value = { ...currentNote.value, ...res.data.data };
            }
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const deleteNote = async (collectionId, noteId) => {
        try {
            const res = await api.delete(`/user/notes/${noteId}`);
            await fetchCollection(collectionId);
            return res.data;
        } catch (e) {
            throw e;
        }
    };

    const toggleNoteComplete = async (noteId, isCompleted) => {
        try {
            const res = await api.put(`/user/notes/${noteId}`, {
                is_completed: isCompleted,
            });
            if (currentNote.value && currentNote.value.id === noteId) {
                currentNote.value.is_completed = res.data.data.is_completed;
            }
            return res.data;
        } catch (e) {
            throw e;
        }
    };

    const fetchUnassignedArticles = async () => {
        try {
            const res = await api.get('/user/unassigned-articles');
            unassignedArticles.value = res.data.data;
            return unassignedArticles.value;
        } catch (e) {
            return [];
        }
    };

    return {
        collections,
        currentCollection,
        currentNote,
        unassignedArticles,
        isLoading,
        isSaving,
        fetchCollections,
        fetchCollection,
        createCollection,
        updateCollection,
        deleteCollection,
        createSection,
        deleteSection,
        createNote,
        fetchNote,
        updateNote,
        deleteNote,
        toggleNoteComplete,
        fetchUnassignedArticles,
    };
}

