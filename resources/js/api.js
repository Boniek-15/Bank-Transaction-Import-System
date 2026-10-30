const headers = {Accept: 'application/json'}

export async function fetchImports()
{
    const res = await fetch('/api/imports',{headers});
    if(!res.ok)
    {
        throw new Error('Failed to load imports')
    }
    return res.json();
}

export async function fetchImport(id)
{
    const res = await fetch('/api/imports/${id}',{headers});
    if(!res.ok)
    {
        throw new Error('Failed to load imports')
    }
    return res.json();
}

export async function uploadFile(file)
{

    const body = new FormData();
    body.append('file',file);
    //|Tu gdzieś jest błąd z POST
    const res = await fetch('/api/imports', {method: POST, headers, body});
    const data = await res.json();
    if (!res.ok)
    {
        throw new Error(data.message ?? 'Upload failed');
    }
    return data;
}
