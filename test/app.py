import streamlit as st
import pandas as pd
import matplotlib.pyplot as plt

# Sample Data
data = {
    "Week": [1, 2, 3, 4, 5, 6],
    "Grade": [75, 80, 85, 78, 88, 92]
}
df = pd.DataFrame(data)

# Streamlit UI
st.set_page_config(page_title="SPAS - Student Performance Analytics", layout="wide")

# Sidebar
with st.sidebar:
    st.title("SPAS")
    st.button("📚 My Courses")
    st.button("📊 Academic Goals")
    st.markdown("---")
    st.text("🔵 John Doe")

# Main Content
st.header("📈 Academic Progress in Math")

# Line Chart with Matplotlib
fig, ax = plt.subplots()
ax.plot(df["Week"], df["Grade"], marker='o', linestyle='-', color='blue', label='Grade')
ax.set_xlabel("Weeks")
ax.set_ylabel("Grades (%)")
ax.set_title("Academic Progress Over Time")
ax.legend()
ax.grid(True)

# Annotate Points
for i, txt in enumerate(df["Grade"]):
    ax.annotate(txt, (df["Week"][i], df["Grade"][i]), textcoords="offset points", xytext=(0,5), ha='center')

st.pyplot(fig)

# Table View
st.subheader("📋 Weekly Grade Summary")
st.dataframe(df.style.set_properties(**{'text-align': 'center'}))
